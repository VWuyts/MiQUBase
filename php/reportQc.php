<?php
/* Copyright (C) 2017-2018 Véronique Wuyts
 * student at Thomas More Mechelen-Antwerpen vzw -- Campus De Nayer
 * Professionele Bachelor Elektronica-ICT
 *
 * MiQUBase is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MiQUBase is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MiQUBase. If not, see <http://www.gnu.org/licenses/>.
 */
	session_start();
	// Check user role
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0
		|| strcmp($_SESSION['role'], 'readonly') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase report', ['report'], null);
		createHeader($_SESSION['user'], true);
		
		// Print report
		if (isset($_POST['excel']))
		{
			$_SESSION['excel'] = true;
			unset($_POST['excel']);
			header("Location: reportQcPrint.php");
		}
		if (isset($_POST['pdf']))
		{
			$_SESSION['excel'] = false;
			unset($_POST['pdf']);
			header("Location: reportQcPrint.php");
		}
		// Unset session variable if back to home page
		if (isset($_POST['home']))
		{
			unset($_POST['home']);
			if (isset($_SESSION['runnumbers'])) unset($_SESSION['runnumbers']);
			if (isset($_SESSION['laneids'])) unset($_SESSION['laneids']);
			if (isset($_SESSION['remarks'])) unset($_SESSION['remarks']);
			if (isset($_SESSION['seqCartridges'])) unset($_SESSION['seqCartridges']);
			if (isset($_SESSION['report'])) unset($_SESSION['report']);
			header("Location: home.php");
			die();
		}
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Get QC parameter data
			if (isset($_POST['laneid']))
			{
				// Create array for QC parameter values
				$qcParameters = [
					'runnumber'			=> null,
					'seqCartridge'		=> null,
					'qScore'			=> null,
					'clusterDensity'	=> null,
					'clustersPF'		=> null,
					'phasing'			=> null,
					'prephasing'		=> null,
					'readsPF'			=> null,
					'aligned'			=> null,
					'remark'			=> null,
				];
				$index = array_search($_POST['laneid'], $_SESSION['laneids']);
				if ($index !== false)
				{
					$qcParameters['runnumber'] =  $_SESSION['runnumbers'][$index];
					$qcParameters['remark'] = $_SESSION['remarks'][$index];
					$qcParameters['seqCartridge'] = $_SESSION['seqCartridges'][$index];
					$query = "SELECT q30, aligned
								FROM summarytotal
								WHERE laneid = $1 AND isnonindexedtotal = $2;";
					$result = pg_query_params($dbconn, $query, [$_POST['laneid'], 'f']);
					if (!$result)
					{
						pg_close($dbconn);
						trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$sumtotal = pg_fetch_array($result, 0);
						if (empty($sumtotal))
						{
							pg_close($dbconn);
							trigger_error('017@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							$qcParameters['qScore'] = $sumtotal['q30'];
							$qcParameters['aligned'] = $sumtotal['aligned'];
							$query = "SELECT density, clusterpf, phasing, prephasing, noreadspf
								FROM ngsread
								WHERE laneid = $1
								ORDER BY phasing DESC;";
							$result = pg_query_params($dbconn, $query, [$_POST['laneid']]);
							if (!$result)
							{
								pg_close($dbconn);
								trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
								die();
							}
							else
							{
								$ngsread = pg_fetch_array($result, 0);
								if (empty($ngsread))
								{
									pg_close($dbconn);
									trigger_error('017@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
									die();
								}
								else
								{
									$qcParameters['clusterDensity'] = $ngsread['density'];
									$qcParameters['clustersPF'] = $ngsread['clusterpf'];
									$qcParameters['phasing'] = $ngsread['phasing'];
									$qcParameters['prephasing'] = $ngsread['prephasing'];
									$qcParameters['readsPF'] = $ngsread['noreadspf'];
								}
							}
						}
					}
				}
				else
				{
					$qcParameters['runnumber'] = null;
					$qcParameters['qScore'] = null;
					$qcParameters['clusterDensity'] = null;
					$qcParameters['clustersPF'] = null;
					$qcParameters['phasing'] = null;
					$qcParameters['prephasing'] = null;
					$qcParameters['readsPF'] = null;
					$qcParameters['aligned'] = null;
					$qcParameters['remark'] = null;
				}
				$_SESSION['report'] = $qcParameters;
			}
			
			// Create date variable to limit the number of retrieved runs to those within the last number of months
			// as specified in de configuration
			$date = (date_sub(date_create(date('Y-m-d')), new DateInterval($_SESSION['months'])))->format('Y-m-d');
			// Create arrays to collect query data
			$_SESSION['runnumbers'] = array();
			$_SESSION['laneids'] = array();
			$_SESSION['remarks'] = array();
			$_SESSION['seqCartridges'] = array();
			// Run number
			$query = "SELECT run.runnumber, run.sequencingcartridge, lane.laneid, run.remark
						FROM run
							LEFT JOIN lane
							ON run.runid = lane.runid
						WHERE lane.laneid IS NOT NULL
							AND run.startdate >= '".$date 
						."' ORDER BY run.runnumber DESC;";
			$result = pg_query($dbconn, $query);
			if (!$result)
			{
				unset($_SESSION['runnumbers']);
				unset($_SESSION['laneids']);
				unset($_SESSION['remarks']);
				unset($_SESSION['seqCartridges']);
				if(isset($_SESSION['report'])) unset($_SESSION['report']);
				pg_close($dbconn);
				trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (($rows = pg_num_rows($result)) == 0)
			{
				echo("\t\t<p class='message'>There are currently no runs with samples loaded into MiQUBase.</p>\n");
				echo("\t\t<p><a href='home.php'>Back to home page</a></p>\n");
			}
			else
			{
				echo("\t\t<h3>Select run number</h3>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
				echo("\t\t\t<p>\n\t\t\t\t<label for='laneid'>Run number:</label>\n");
				echo("\t\t\t\t<select id='laneid' class='marginRight' name='laneid' size='1' autofocus>\n");
				while($arr = pg_fetch_array($result))
				{
					array_push($_SESSION['runnumbers'], $arr['runnumber']);
					array_push($_SESSION['laneids'], $arr['laneid']);
					array_push($_SESSION['remarks'], $arr['remark']);
					array_push($_SESSION['seqCartridges'], $arr['sequencingcartridge']);
					if (isset($_POST['laneid']) && $_POST['laneid'] == $arr['laneid'])
					{
						echo("\t\t\t\t\t<option value=".$arr['laneid']." selected>". $arr['runnumber'] ."</option>\n");
					}
					else
					{
						echo("\t\t\t\t\t<option value=".$arr['laneid'].">". $arr['runnumber'] ."</option>\n");
					}
				}
				echo("\t\t\t\t</select>\n");
				echo("\t\t\t\t<input class='ok' type='submit' name='runnumber' value='OK' />\n\t\t\t</p>\n");
				echo("\t\t</form>\n");
			}
			if (isset($_POST['laneid']))
			{
				// Overview QC parameters
				echo("\t\t<h1>QC parameters</h1>\n");
				echo("\t\t<table>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Run number:</td>\n\t\t\t\t<td>");
				if (!empty($_SESSION['report']['runnumber']))
				{
					echo($_SESSION['report']['runnumber']);
				}
				else
				{
					echo("<i>Select a run and click ok</i>");
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>MiSeq reagent kit:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['seqCartridge']))
				{
					echo($qcParameters['seqCartridge']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Average Q score &lpar;&percnt;&rpar;:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['qScore']))
				{
					echo($qcParameters['qScore']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Cluster density &lpar;K&sol;mm&sup2;&rpar;:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['clusterDensity']))
				{
					echo($qcParameters['clusterDensity']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Clusters passing filter &lpar;&percnt;&rpar;:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['clustersPF']))
				{
					echo($qcParameters['clustersPF']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Phasing&sol;Prephasing &lpar;max &percnt;&rpar;:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['phasing']) && !empty($qcParameters['prephasing']))
				{
					echo($qcParameters['phasing'] ."&sol;". $qcParameters['prephasing']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Reads passing filter (millions):</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['readsPF']))
				{
					echo($qcParameters['readsPF']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Aligned &lsqb;to PhiX control&rsqb; &lpar;&percnt;&rpar;:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['aligned']))
				{
					echo($qcParameters['aligned']);
				}
				echo("</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Remarks:</td>\n\t\t\t\t<td>");
				if (!empty($qcParameters['remark']))
				{
					echo(htmlspecialchars($qcParameters['remark']));
				}
				echo("</td>\n\t\t\t</tr>\n");	
				echo("\t\t</table>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<input class='button buttonMarginRight' type='submit' name='home' value='Back to home page' />\n");
				echo("\t\t\t\t<input class='button buttonMarginRight' type='submit' name='excel' value='Print to Excel' />\n");
				echo("\t\t\t\t<input class='button' type='submit' name='pdf' value='Print to pdf' />\n");
				echo("\t\t\t</div>\n\t\t</form>\n");
			}
			pg_close($dbconn);
		}
		
		createFooter(true);
	}
	else
	{
		// Session variable isn't registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>