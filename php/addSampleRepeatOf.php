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
	// Check required session variables
	if (isset($_SESSION['inputArr']) && isset($_SESSION['role'])
		&& (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase sample check repeat', ['actions'], null);
		createHeader($_SESSION['user'], true);
	
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			unset($_SESSION['inputArr']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			if (isset($_POST['addToDb']))
			{
				$remarkArr = array();
				for ($i = 0; $i < count($_SESSION['inputArr']['noSamplesForRemark']); $i++)
				{
					for ($j = 0; $j < $_SESSION['inputArr']['noSamplesForRemark'][$i]; $j++)
					{
						array_push($remarkArr, $_SESSION['initials'] ." ". date('d/m/Y') .": ".cleanInputText($_POST['remark'][$i]));
					}
				}
				$remarkCounter = 0;
				for ($i = 0; $i < count($_SESSION['inputArr']['sampleArr']); $i++)
				{
					if ($_SESSION['laneIdArr'][$i] == -1)
					{
						$_SESSION['inputArr']['sampleArr'][$i]['isrepeatof'] = null;
					}
					else
					{
						//add remarks
						if (!empty($remarkArr[$remarkCounter]) && strcmp($remarkArr[$remarkCounter], ' ') != 0)
						{
							if (empty($_SESSION['inputArr']['sampleArr'][$i]['remark']))
							{
								$_SESSION['inputArr']['sampleArr'][$i]['remark'] = $remarkArr[$remarkCounter];
							}
							else
							{
								$_SESSION['inputArr']['sampleArr'][$i]['remark'] .= ' # ';
								$_SESSION['inputArr']['sampleArr'][$i]['remark'] .= $remarkArr[$remarkCounter];
							}
						}
						$remarkCounter++;
						if ($_POST[$i] == -1)
						{
							$_SESSION['inputArr']['sampleArr'][$i]['isrepeatof'] = null;
						}
						else
						{
							$_SESSION['inputArr']['sampleArr'][$i]['isrepeatof'] = $_POST[$i];
						}
					}
				}
				unset($_POST['addToDb']);
				unset($_POST['remark']);
				unset($_SESSION['inputArr']['noSamplesForRemark']);
				unset($_SESSION['laneIdArr']);
				header("Location: addSampleToDb.php");
				die();
			}
			
			//Create array to keep track of laneId's of possible repeats
			$_SESSION['laneIdArr'] = array();
			// Create query to search for duplicate sample names
			$query = "SELECT sample.laneid, run.runnumber
						FROM sample
							LEFT JOIN lane
							ON sample.laneid = lane.laneid
							LEFT JOIN run
							ON lane.runid = run.runid
						WHERE upper(sample.samplename) = upper($1)
							
						ORDER BY sample.laneid ASC";
			for ($i = 0; $i < count($_SESSION['inputArr']['sampleArr']); $i++)
			{
				$result = pg_query_params($dbconn, $query, [$_SESSION['inputArr']['sampleArr'][$i]['samplename']]);
				if (!$result)
				{
					pg_close($dbconn);
					unset($_SESSION['inputArr']);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				elseif (pg_num_rows($result) == 0)
				{
					array_push($_SESSION['laneIdArr'], -1);
				}
				else
				{
					if (!($id = pg_fetch_all($result)))
					{
						pg_close($dbconn);
						unset($_SESSION['inputArr']);
						trigger_error('020@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					array_push($_SESSION['laneIdArr'], $id);
				}
			}
			// Show overview of samples that are possible repeats
			$currentDivision = $_SESSION['inputArr']['division'][0];
			$currentProject = $_SESSION['inputArr']['project'][0];
			$currentSpecies = $_SESSION['inputArr']['species'][0];
			// Clear array to keep track of number of samples for which remarks inputs are valid
			$_SESSION['inputArr']['noSamplesForRemark'] = array();
			$counter = 0;
			echo("\t\t<h1>Check if samples of run ". $_SESSION['inputArr']['runNumber'] ." are repeats</h1>\n");
			echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
			echo("\t\t\t<table>\n\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
			echo("\t\t\t\t\t<td>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project:</td>\n");
			echo("\t\t\t\t\t<td>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
			echo("\t\t\t\t\t<td>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
			for ($i = 0; $i < count($_SESSION['inputArr']['sampleArr']); $i++)
			{
				if (strcmp($currentDivision, $_SESSION['inputArr']['division'][$i]) == 0
					&& strcmp($currentProject, $_SESSION['inputArr']['project'][$i]) == 0
					&& strcmp($currentSpecies, $_SESSION['inputArr']['species'][$i]) == 0)
				{
					if ($_SESSION['laneIdArr'][$i] !== -1)
					{
						$counter++;
						if ($counter == 1)
						{
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Samples:</td>\n");
						}
						else
						{
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n");
						}
						echo("\t\t\t\t\t<td>".$_SESSION['inputArr']['sampleArr'][$i]['samplename']."\n");
						echo("\t\t\t\t\t\t<select class='runnumber' name='". $i ."' size='1'>\n");
						for ($j = 0; $j < count($_SESSION['laneIdArr'][$i]); $j++)
						{
							echo("\t\t\t\t\t\t\t<option value=".$_SESSION['laneIdArr'][$i][$j]['laneid'] .">". $_SESSION['laneIdArr'][$i][$j]['runnumber'] ."</option>\n");
						}
						echo("\t\t\t\t\t\t\t<option value='-1'>no repeat</option>\n");
						echo("\t\t\t\t\t\t</select>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
					}
				}
				else
				{
					if ($counter == 0)
					{
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Samples:</td><td>No repeats found</td>\n\t\t\t\t</tr>\n");
					}
					else
					{
						array_push($_SESSION['inputArr']['noSamplesForRemark'], $counter);
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
						echo("\t\t\t\t\t<td><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
					}
					$currentDivision = $_SESSION['inputArr']['division'][$i];
					$currentProject = $_SESSION['inputArr']['project'][$i];
					$currentSpecies = $_SESSION['inputArr']['species'][$i];
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='2'></td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
					echo("\t\t\t\t\t<td>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project:</td>\n");
					echo("\t\t\t\t\t<td>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
					echo("\t\t\t\t\t<td>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
					$counter = 0;
					$i--;
				}
			}
			if ($counter == 0)
			{
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Samples:</td><td>No repeats found</td>\n\t\t\t\t</tr>\n");
			}
			else
			{
				array_push($_SESSION['inputArr']['noSamplesForRemark'], $counter);
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
				echo("\t\t\t\t\t<td><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
			}
			echo("\t\t\t</table>\n");
			echo("\t\t\t<div class='buttonBox'>\n");
			echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
			echo("\t\t\t\t<a class='buttonMarginRight' href='addSampleClear.php'>Clear</a>\n");
			echo("\t\t\t\t<input class='button' type='submit' name='addToDb' value='Add to database' />\n");
			echo("\t\t\t</div>\n\t\t</form>\n");
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