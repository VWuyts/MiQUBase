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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		$script = "\t\t\tvar checkbutns = document.getElementsByClassName('checkbutton');\n";
		$script .= "\t\t\tvar uncheckbutns = document.getElementsByClassName('uncheckbutton');\n";
		$script .= "\t\t\tfor (var butn in checkbutns){checkbutns[butn].onclick = checkAll;}\n";
		$script .= "\t\t\tfor (var butn in uncheckbutns){uncheckbutns[butn].onclick = uncheckAll;}\n";
		$script .= "\t\t\tfunction checkAll(){\n";
		$script .= "\t\t\t\tvar checks = document.getElementsByClassName(this.name);\n";
		$script .= "\t\t\t\tif (checks){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < checks.length; i++){\n";
		$script .= "\t\t\t\t\t\tchecks[i].checked = true;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n";
		$script .= "\t\t\tfunction uncheckAll(){\n";
		$script .= "\t\t\t\tvar checks = document.getElementsByClassName(this.name);\n";
		$script .= "\t\t\t\tif (checks){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < checks.length; i++){\n";
		$script .= "\t\t\t\t\t\tchecks[i].checked = false;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}";
		createHead(true, 'MiQUBase sample', ['actions'], $script);
		createHeader($_SESSION['user'], true);
		
		// Create connection to database
		$dbconn = false;
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Add repeats and remarks to database
			if (isset($_POST['addToRepeat']))
			{
				if (empty($_POST['sample']))
				{
					unset($_POST['run']);
					unset($_POST['laneid']);
					unset($_POST['addToRepeat']);
					unset($_POST['sample']);
					unset($_POST['remark']);
					unset($_SESSION['noSamples']);
					unset($_SESSION['oldremarkArr']);
					unset($_SESSION['sampleidArr']);
					unset($_SESSION['runnumber']);
					pg_close($dbconn);
					// Redirect user to message page
					createMessagePage(["No samples of run ". $_SESSION['runnumber'], "have been marked 'to repeat' in MiQUBase."],
						$_SESSION['user'], "../php/home.php", "Back to home page");
					die();
				}
				else
				{
					// Add remarks to remarkArr
					$remarkArr = array();
					$newRemarkArr = array();
					for ($i = 1; $i < count($_SESSION['noSamples']); $i++)
					{
						$_SESSION['noSamples'][$i] += $_SESSION['noSamples'][$i - 1];
					}
					for ($i = 0; $i < count($_POST['remark']); $i++)
					{
						$remark = cleanInputText($_POST['remark'][$i]);
						$minIndex = 0;
						$maxindex = $_SESSION['noSamples'][0];
						if ($i > 0)
						{
							$minIndex = $_SESSION['noSamples'][$i - 1];
							$maxindex = $_SESSION['noSamples'][$i];
						}
						for ($j = $minIndex; $j < $maxindex; $j++)
						{
							if (!empty($remark))
							{
								$newRemarkArr[$j] = $_SESSION['initials'] ." ". date('d/m/Y') .": ". $remark;
								if (!empty($_SESSION['oldremarkArr'][$j]))
								{
									$remarkArr[$j] = $_SESSION['oldremarkArr'][$j] ." - ". $_SESSION['initials'] ." ". date('d/m/Y') .": ". $remark;
								}
								else
								{
									$remarkArr[$j] = $_SESSION['initials'] ." ". date('d/m/Y') .": ". $remark;
								}
							}
							else
							{
								$remarkArr[$j] = $_SESSION['oldremarkArr'][$j];
								$newRemarkArr[$j] = null;
							}
						}
					}
					// Set sample ids as keys
					$remarkArr = array_combine($_SESSION['sampleidArr'], $remarkArr);
					$newRemarkArr = array_combine($_SESSION['sampleidArr'], $newRemarkArr);
					$samplesToRepeat = count($_POST['sample']);
					// Create update query
					$query = "UPDATE sample
								SET torepeat=TRUE
								WHERE sampleid=$1;";
					$queryRemark = "UPDATE sample
									SET torepeat=TRUE, remark=$1
									WHERE sampleid=$2;";
					$result = false;
					for ($i = 0; $i < $samplesToRepeat; $i++)
					{
						$params = array();
						if (!empty($remarkArr[$_POST['sample'][$i]]))
						{
							$params[] = $remarkArr[$_POST['sample'][$i]];
							$params[] = $_POST['sample'][$i];
							$result = pg_query_params($dbconn, $queryRemark, $params);
						}
						else
						{
							$params[] = $_POST['sample'][$i];
							$result = pg_query_params($dbconn, $query, $params);
						}
						if (!$result)
						{
							unset($_POST['run']);
							unset($_POST['laneid']);
							unset($_POST['addToRepeat']);
							unset($_POST['sample']);
							unset($_POST['remark']);
							unset($_SESSION['noSamples']);
							unset($_SESSION['oldremarkArr']);
							unset($_SESSION['sampleidArr']);
							unset($_SESSION['runnumber']);
							pg_close($dbconn);
							trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							// Create log message
							$activitylogger->info('adding torepeat to db succeeded', ['user'=>$_SESSION['user'], 'runnumber'=>$_SESSION['runnumber'] , 'sampleid'=>$_POST['sample'][$i], 'remark'=>$newRemarkArr[$_POST['sample'][$i]]]);
						}	
					}			
				}
				unset($_POST['run']);
				unset($_POST['laneid']);
				unset($_POST['addToRepeat']);
				unset($_POST['sample']);
				unset($_POST['remark']);
				unset($_SESSION['noSamples']);
				unset($_SESSION['oldremarkArr']);
				unset($_SESSION['sampleidArr']);
				pg_close($dbconn);
				// Redirect user to message page
				createMessagePage([$samplesToRepeat ." samples of run ".$_SESSION['runnumber'], "have been marked 'to repeat' in MiQUBase."],
					$_SESSION['user'], "../php/home.php", "Back home page");
				unset($_SESSION['runnumber']);
				die();
			}
			
			// Get samples corresponding to the selected lane id
			if (isset($_POST['run']) && !empty($_POST['laneid']))
			{
				// Create arrays to collect query results
				$_SESSION['sampleidArr'] = array();
				$samplenameArr = array();
				$_SESSION['oldremarkArr'] = array();
				$projectArr = array();
				$divisionArr = array();
				$speciesArr = array();
				$query = "SELECT sample.sampleid, sample.samplename, sample.remark,
								project.projectnumber,
								division.divisionname,
								species.speciesname
							FROM sample
								LEFT JOIN project
								ON sample.projectid = project.projectid
								LEFT JOIN division
								ON project.divisionid = division.divisionid
								LEFT JOIN species
								ON sample.speciesid = species.speciesid
							WHERE sample.laneid=$1 AND sample.torepeat=FALSE;";
				$result = pg_query_params($dbconn, $query, [$_POST['laneid']]);
				if (!$result)
				{
					if (isset($_POST['run'])) unset($_POST['run']);
					if (isset($_POST['laneid'])) unset($_POST['laneid']);
					if (isset($_POST['addToRepeat'])) unset($_POST['addToRepeat']);
					if (isset($_POST['sample'])) unset($_POST['sample']);
					if (isset($_POST['remark'])) unset($_POST['remark']);
					if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
					if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
					if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
					if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
					pg_close($dbconn);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				elseif (pg_num_rows($result) == 0)
				{
					echo("\t\t<p class='message'>There are no samples in run ". $_SESSION['runnumber'] ."</p>\n"); 
					echo("\t\t<p class='message'>that can be marked to be repeated.</p>\n");
					echo("\t\t<p><a class='homelink' href='home.php'>Back to home page</a></p>\n");
					if (isset($_POST['run'])) unset($_POST['run']);
					if (isset($_POST['laneid'])) unset($_POST['laneid']);
					if (isset($_POST['addToRepeat'])) unset($_POST['addToRepeat']);
					if (isset($_POST['sample'])) unset($_POST['sample']);
					if (isset($_POST['remark'])) unset($_POST['remark']);
					if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
					if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
					if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
					if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
					pg_close($dbconn);
					die();
				}
				else
				{
					// Fill arrays with query results
					while ($arr = pg_fetch_array($result))
					{
						$_SESSION['sampleidArr'][] = $arr['sampleid'];
						$samplenameArr[] = $arr['samplename'];
						$oldremark = trim($arr['remark']);
						if (!empty($oldremark))
						{
							$_SESSION['oldremarkArr'][] = $oldremark;
						}
						else
						{
							$_SESSION['oldremarkArr'][] = null;
						}
						$projectArr[] = $arr['projectnumber'];
						$divisionArr[] = $arr['divisionname'];
						$speciesArr[] = $arr['speciesname'];
					}
					// Set sample id as key
					$samplenameArr = array_combine($_SESSION['sampleidArr'], $samplenameArr);
					$projectArr = array_combine($_SESSION['sampleidArr'], $projectArr);
					$divisionArr = array_combine($_SESSION['sampleidArr'], $divisionArr);
					$speciesArr = array_combine($_SESSION['sampleidArr'], $speciesArr);
				}
			}
			
			// Create date variable to limit the number of retrieved runs to those within the last 12 months
			$date = (date_sub(date_create(date('Y-m-d')), new DateInterval('P12M')))->format('Y-m-d');
			// Create array to keep track of number of samples for which remarks are valid
			$_SESSION['noSamples'] = array();
			// first step is to select run number
			$_SESSION['runnumber'] = '';
			$query = "SELECT run.runnumber, lane.laneid
						FROM run
							LEFT JOIN lane
							ON run.runid = lane.runid
						WHERE lane.laneid IS NOT NULL
							AND run.startdate >='".$date
						."' ORDER BY run.runnumber DESC;";
			$result = pg_query($dbconn, $query);
			if (!$result)
			{
				if (isset($_POST['run'])) unset($_POST['run']);
				if (isset($_POST['laneid'])) unset($_POST['laneid']);
				if (isset($_POST['addToRepeat'])) unset($_POST['addToRepeat']);
				if (isset($_POST['sample'])) unset($_POST['sample']);
				if (isset($_POST['remark'])) unset($_POST['remark']);
				if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
				if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
				if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
				if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
				pg_close($dbconn);
				trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (($rows = pg_num_rows($result)) == 0)
			{
				echo("\t\t<p class='message'>There are currently no runs with samples loaded into MiQUBase.</p>\n");
				echo("\t\t<p><a class='homelink' href='home.php'>Back to home page</a></p>\n");
			}
			else
			{
				echo("\t\t<h3>Select run number</h3>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
				echo("\t\t\t<p>\n\t\t\t\t<label for='laneid'>Run number:</label>\n");
				echo("\t\t\t\t<select id='laneid' class='marginRight' name='laneid' size='1' autofocus>\n");
				while($arr = pg_fetch_array($result))
				{
					if (isset($_POST['laneid']) && $_POST['laneid'] == $arr['laneid'])
					{
						echo("\t\t\t\t\t<option value=".$arr['laneid']." selected>". $arr['runnumber'] ."</option>\n");
						$_SESSION['runnumber'] = $arr['runnumber'];
					}
					else
					{
						echo("\t\t\t\t\t<option value=".$arr['laneid'].">". $arr['runnumber'] ."</option>\n");
					}
				}
				echo("\t\t\t\t</select>\n");
				echo("\t\t\t\t<input class='ok' type='submit' name='run' value='OK' />\n");
				echo("\t\t\t</p>\n");
				if (!isset($_POST['run']))
				{
					echo("\t\t\t<div class='buttonBox'>\n");
					echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
					echo("\t\t\t</div>\n");
				}
				echo("\t\t</form>\n");
			}
			
			// Proceed to samples if run has been selected
			if (isset($_POST['run']) && !empty($_POST['laneid']))
			{
				// Show overview of samples
				$currentDivision = $divisionArr[$_SESSION['sampleidArr'][0]];
				$currentProject = $projectArr[$_SESSION['sampleidArr'][0]];
				$currentSpecies = $speciesArr[$_SESSION['sampleidArr'][0]];
				$counter = 0;
				// Create class for checkAll/uncheckAll buttons
				$class = '';
				
				echo("\t\t<h1 class='top'>Check which samples of run ". $_SESSION['runnumber'] ." have to be repeated</h1>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
				echo("\t\t\t<table>\n");
				for ($i = 0; $i < count($_SESSION['sampleidArr']); $i++)
				{
					if (strcmp($currentDivision, $divisionArr[$_SESSION['sampleidArr'][$i]]) == 0
						&& strcmp($currentProject, $projectArr[$_SESSION['sampleidArr'][$i]]) == 0
						&& strcmp($currentSpecies, $speciesArr[$_SESSION['sampleidArr'][$i]]) == 0)
					{
						$counter++;
						if ($counter == 1)
						{
							$class = $samplenameArr[$_SESSION['sampleidArr'][$i]];
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
							echo("\t\t\t\t\t<td colspan='2'>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project:</td>\n");
							echo("\t\t\t\t\t<td colspan='2'>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
							echo("\t\t\t\t\t<td colspan='2'>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Samples:</td>\n");
						}
						elseif ($counter % 2 == 1)
						{
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n");
						}
						echo("\t\t\t\t\t<td>\n\t\t\t\t\t\t<input class='samples ".$class."' type='checkbox' name=sample[] value='". $_SESSION['sampleidArr'][$i] ."' />". $samplenameArr[$_SESSION['sampleidArr'][$i]] ."\n\t\t\t\t\t</td>\n");
						if ($counter % 2 == 0)
						{
							echo("\t\t\t\t</tr>\n");
						}
					}
					else
					{
						if ($counter % 2 != 0)
						{
							echo("\t\t\t\t\t<td></td>\n\t\t\t\t</tr>\n");
						}
						if ($counter != 0)
						{
							$_SESSION['noSamples'][] = $counter;
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
							echo("\t\t\t\t\t<td colspan='2'><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'>\n\t\t\t\t\t\t<input name='".$class."' class='checkbutton' type='button' value='Check all samples' />\n");
							echo("\t\t\t\t\t\t<input name='".$class."' class='uncheckbutton' type='button' value='Uncheck all samples' />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
						}
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n\t\t\t\t\t<td colspan='3'></td>\n\t\t\t\t</tr>\n");
						$currentDivision = $divisionArr[$_SESSION['sampleidArr'][$i]];
						$currentProject = $projectArr[$_SESSION['sampleidArr'][$i]];
						$currentSpecies = $speciesArr[$_SESSION['sampleidArr'][$i]];
						$counter = 0;
						$i--;
					}
				}
				if ($counter % 2 != 0)
				{
					echo("\t\t\t\t\t<td>&nbsp;</td>\n\t\t\t\t</tr>\n");
				}
				if ($counter != 0)
				{
					$_SESSION['noSamples'][] = $counter;
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
					echo("\t\t\t\t\t<td colspan='2'><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'>\n\t\t\t\t\t\t<input name='".$class."' class='checkbutton' type='button' value='Check all samples' />\n");
					echo("\t\t\t\t\t\t<input name='".$class."' class='uncheckbutton' type='button' value='Uncheck all samples' />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
				}
				echo("\t\t\t</table>\n");
				echo("\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
				echo("\t\t\t\t<input class='button buttonMarginRight' type='reset' value='Clear' />\n");
				echo("\t\t\t\t<input class='button' type='submit' name='addToRepeat' value='Add to MiQUBase' />\n");
				echo("\t\t\t</div>\n\t\t</form>\n");
			}
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