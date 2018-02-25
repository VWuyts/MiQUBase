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
			// Create error variable
			$dateErr = "";
			// Create boolean for input check
			$inputOK = true;
			
			// Add result sent dates and remarks to input array
			if (isset($_POST['next']))
			{
				if (empty($_POST['sample']))
				{
					unset($_POST['run']);
					unset($_POST['laneid']);
					unset($_POST['next']);
					unset($_POST['sample']);
					unset($_POST['remark']);
					unset($_SESSION['runstartdate']);
					unset($_SESSION['sampleidArr']);
					unset($_SESSION['samplenameArr']);
					unset($_SESSION['isrepeatofArr']);
					unset($_SESSION['oldremarkArr']);
					unset($_SESSION['projectArr']);
					unset($_SESSION['divisionArr']);
					unset($_SESSION['speciesArr']);
					unset($_SESSION['noSamples']);
					pg_close($dbconn);
					// Redirect user to message page
					createMessagePage(["No samples of run ". $_SESSION['runnumber'], "have been marked for addition of result sent date in MiQUBase."],
						$_SESSION['user'], "../php/home.php", "Back to home page");
					unset($_SESSION['runnumber']);
					die();
				}
				else
				{
					// Add result sent dates to dateArr and remarks to remarkArr
					$dateArr = array();
					$remarkArr = array();
					$newRemarkArr = array();
					for ($i = 1; $i < count($_SESSION['noSamples']); $i++)
					{
						$_SESSION['noSamples'][$i] += $_SESSION['noSamples'][$i - 1];
					}
					for ($i = 0; $i < count($_POST['date']); $i++)
					{
						// Clean input
						$resultSentDate = getValidDate(cleanInputText($_POST['date'][$i]));
						$remark = cleanInputText($_POST['remark'][$i]);
						// Put in array
						$minIndex = 0;
						$maxindex = $_SESSION['noSamples'][0];
						if ($i > 0)
						{
							$minIndex = $_SESSION['noSamples'][$i - 1];
							$maxindex = $_SESSION['noSamples'][$i];
						}
						for ($j = $minIndex; $j < $maxindex; $j++)
						{
							$dateArr[] = $resultSentDate;
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
					unset($_SESSION['noSamples']);
					unset($_SESSION['oldremarkArr']);
					// Set sample ids as keys
					$dateArr = array_combine($_SESSION['sampleidArr'], $dateArr);
					$remarkArr = array_combine($_SESSION['sampleidArr'], $remarkArr);
					$newRemarkArr = array_combine($_SESSION['sampleidArr'], $newRemarkArr);
					$samplesToSetDate = count($_POST['sample']);
					// Check if every selected sample has a valid date
					while ($inputOK && $i < $samplesToSetDate)
					{
						// Check if date is valid
						if ($dateArr[$_POST['sample'][$i]] == false)
						{
							$dateErr = "Date in format dd/mm/yyyy is required";
							$inputOK = false;
						}
						// Check if result sent date is greater than or equal to run start date
						elseif (((date_diff(date_create($_SESSION['runstartdate']), date_create($dateArr[$_POST['sample'][$i]])))->format('%R%a')) < 0)
						{
							$dateErr = "Result sent date should be after run start date";
							$inputOK = false;
						}
						$i++;
					}
					
					// Check if there are any samples to be updated
					if ($inputOK)
					{
						// Create update query
						$query = "UPDATE sample
									SET resultsentdate = $1
									WHERE sampleid=$2;";
						$queryRemark = "UPDATE sample
										SET remark=$1, resultsentdate = $2
										WHERE sampleid=$3;";
						$result = false;
						$keys = array();
						$checkRepeats = false;
						for ($i = 0; $i < $samplesToSetDate; $i++)
						{
							$keys[] = $_POST['sample'][$i];
							$params = array();
							if (!empty($_SESSION['isrepeatofArr'][$_POST['sample'][$i]]) 
								|| $_SESSION['isrepeatofArr'][$_POST['sample'][$i]] == "0")
							{
								$checkRepeats = true;
							}
							if ($dateArr[$_POST['sample'][$i]] !== false)
							{
								if (!empty($remarkArr[$_POST['sample'][$i]]))
								{
									$params[] = $remarkArr[$_POST['sample'][$i]];
									$params[] = $dateArr[$_POST['sample'][$i]];
									$params[] = $_POST['sample'][$i];
									$result = pg_query_params($dbconn, $queryRemark, $params);
								}
								else
								{
									$params[] = $dateArr[$_POST['sample'][$i]];
									$params[] = $_POST['sample'][$i];
									$result = pg_query_params($dbconn, $query, $params);
								}
								if (!$result)
								{
									unset($_POST['run']);
									unset($_POST['laneid']);
									unset($_POST['next']);
									unset($_POST['sample']);
									unset($_POST['remark']);
									unset($_SESSION['runnumber']);
									unset($_SESSION['runstartdate']);
									unset($_SESSION['sampleidArr']);
									unset($_SESSION['samplenameArr']);
									unset($_SESSION['isrepeatofArr']);
									unset($_SESSION['projectArr']);
									unset($_SESSION['divisionArr']);
									unset($_SESSION['speciesArr']);
									pg_close($dbconn);
									trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
									die();
								}
								else
								{
									// Create log message
									$activitylogger->info('adding result sent date to db succeeded', ['user'=>$_SESSION['user'], 'runnumber'=>$_SESSION['runnumber'] , 'sampleid'=>$_POST['sample'][$i], 'remark'=>$newRemarkArr[$_POST['sample'][$i]]]);
								}
							}
						}
						pg_close($dbconn);
						unset($_POST['run']);
						unset($_POST['laneid']);
						unset($_POST['next']);
						unset($_POST['sample']);
						unset($_POST['remark']);
						unset($_SESSION['runstartdate']);
						unset($_SESSION['sampleidArr']);
						if (!$checkRepeats)
						{
							unset($_SESSION['samplenameArr']);
							unset($_SESSION['isrepeatofArr']);
							unset($_SESSION['projectArr']);
							unset($_SESSION['divisionArr']);
							unset($_SESSION['speciesArr']);
							// Redirect user to message page
							createMessagePage(["For ".$samplesToSetDate ." samples of run ".$_SESSION['runnumber'], "a result sent date has been added in MiQUBase."],
								$_SESSION['user'], "../php/home.php", "Back to home page");
							unset($_SESSION['runnumber']);
							die();
						}
						else
						{
							$_SESSION['keys'] = $keys;
							// Redirect user to page to check field 'isrepeatof'
							header("Location: addResultSentDateRepeat.php");
							die();
						}
					}	
				}
			}
			
			// Get data of samples if run has been selected
			// Create arrays for query results
			$sampleidArr = array();
			$samplenameArr = array();
			$oldremarkArr = array();
			$isrepeatofArr = array();
			$projectArr = array();
			$divisionArr = array();
			$speciesArr = array();
			if (!empty($_POST['laneid']))
			{
				// Get samples corresponding to the selected lane id and which do not have a result sent date
				$query = "SELECT sample.sampleid, sample.samplename, sample.remark, sample.isrepeatof,
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
							WHERE sample.laneid=$1 AND sample.resultsentdate IS NULL
							ORDER BY sample.samplename ASC;";
				$result = pg_query_params($dbconn, $query, [$_POST['laneid']]);
				if (!$result)
				{
					if (isset($_POST['run'])) unset($_POST['run']);
					if (isset($_POST['laneid'])) unset($_POST['laneid']);
					if (isset($_POST['next'])) unset($_POST['next']);
					if (isset($_POST['sample'])) unset($_POST['sample']);
					if (isset($_POST['remark'])) unset($_POST['remark']);
					if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
					if (isset($_SESSION['runstartdate'])) unset($_SESSION['runstartdate']);
					if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
					if (isset($_SESSION['samplenameArr'])) unset($_SESSION['samplenameArr']);
					if (isset($_SESSION['isrepeatofArr'])) unset($_SESSION['isrepeatofArr']);
					if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
					if (isset($_SESSION['projectArr'])) unset($_SESSION['projectArr']);
					if (isset($_SESSION['divisionArr'])) unset($_SESSION['divisionArr']);
					if (isset($_SESSION['speciesArr'])) unset($_SESSION['speciesArr']);
					if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
					pg_close($dbconn);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				elseif (pg_num_rows($result) == 0)
				{
					echo("\t\t<p class='message'>There are no samples in run ". $_SESSION['runnumber'] ."</p>\n"); 
					echo("\t\t<p class='message'>for which a result sent date can be added.</p>\n");
					echo("\t\t<p><a class='homelink' href='home.php'>Back to home page</a></p>\n");
					if (isset($_POST['run'])) unset($_POST['run']);
					if (isset($_POST['laneid'])) unset($_POST['laneid']);
					if (isset($_POST['next'])) unset($_POST['next']);
					if (isset($_POST['sample'])) unset($_POST['sample']);
					if (isset($_POST['remark'])) unset($_POST['remark']);
					if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
					if (isset($_SESSION['runstartdate'])) unset($_SESSION['runstartdate']);
					if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
					if (isset($_SESSION['samplenameArr'])) unset($_SESSION['samplenameArr']);
					if (isset($_SESSION['isrepeatofArr'])) unset($_SESSION['isrepeatofArr']);
					if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
					if (isset($_SESSION['projectArr'])) unset($_SESSION['projectArr']);
					if (isset($_SESSION['divisionArr'])) unset($_SESSION['divisionArr']);
					if (isset($_SESSION['speciesArr'])) unset($_SESSION['speciesArr']);
					if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
					pg_close($dbconn);
					die();
				}
				else
				{
					// Fill arrays with query results
					while ($arr = pg_fetch_array($result))
					{
						$sampleidArr[] = $arr['sampleid'];
						$samplenameArr[] = $arr['samplename'];
						$oldremark = trim($arr['remark']);
						if (!empty($oldremark))
						{
							$oldremarkArr[] = $oldremark;
						}
						else
						{
							$oldremarkArr[] = null;
						}
						$isrepeatofArr[] = $arr['isrepeatof'];
						$projectArr[] = $arr['projectnumber'];
						$divisionArr[] = $arr['divisionname'];
						$speciesArr[] = $arr['speciesname'];
					}
					// Set sample id as key
					$samplenameArr = array_combine($sampleidArr, $samplenameArr);
					$isrepeatofArr = array_combine($sampleidArr, $isrepeatofArr);
					$projectArr = array_combine($sampleidArr, $projectArr);
					$divisionArr = array_combine($sampleidArr, $divisionArr);
					$speciesArr = array_combine($sampleidArr, $speciesArr);
					// Set session variables
					$_SESSION['sampleidArr'] = $sampleidArr;
					$_SESSION['samplenameArr'] = $samplenameArr;
					$_SESSION['isrepeatofArr'] = $isrepeatofArr;
					$_SESSION['oldremarkArr'] = $oldremarkArr;
					$_SESSION['projectArr'] = $projectArr;
					$_SESSION['divisionArr'] = $divisionArr;
					$_SESSION['speciesArr'] = $speciesArr;
				}
			}
			
			// Show form
			// Create date variable to limit the number of retrieved runs to those within the last 12 months
			//$date = (date_sub(date_create(date('Y-m-d')), new DateInterval('P12M')))->format('Y-m-d');
			// Create array to keep track of number of samples for which result sent dates are valid
			$noSamples = array();
			
			// first step is to select run number
			$_SESSION['runnumber'] = '';
			$query = "SELECT DISTINCT run.runnumber, lane.laneid, run.startdate
						FROM run
							LEFT JOIN lane
							ON run.runid = lane.runid
							LEFT JOIN sample
							ON lane.laneid = sample.laneid
						WHERE lane.laneid IS NOT NULL
							AND sample.resultsentdate IS NULL
						ORDER BY run.runnumber DESC;";
			$result = pg_query($dbconn, $query);
			if (!$result)
			{
				if (isset($_POST['run'])) unset($_POST['run']);
				if (isset($_POST['laneid'])) unset($_POST['laneid']);
				if (isset($_POST['next'])) unset($_POST['next']);
				if (isset($_POST['sample'])) unset($_POST['sample']);
				if (isset($_POST['remark'])) unset($_POST['remark']);
				if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
				if (isset($_SESSION['runstartdate'])) unset($_SESSION['runstartdate']);
				if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
				if (isset($_SESSION['samplenameArr'])) unset($_SESSION['samplenameArr']);
				if (isset($_SESSION['isrepeatofArr'])) unset($_SESSION['isrepeatofArr']);
				if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
				if (isset($_SESSION['projectArr'])) unset($_SESSION['projectArr']);
				if (isset($_SESSION['divisionArr'])) unset($_SESSION['divisionArr']);
				if (isset($_SESSION['speciesArr'])) unset($_SESSION['speciesArr']);
				if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
				pg_close($dbconn);
				trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (($rows = pg_num_rows($result)) == 0)
			{
				echo("\t\t<p class='message'>There are currently no samples without result sent date loaded into MiQUBase.</p>\n");
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
						$_SESSION['runstartdate'] = $arr['startdate'];
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
			if (!empty($sampleidArr))
			{
				// Show overview of samples
				$currentDivision = $divisionArr[$sampleidArr[0]];
				$currentProject = $projectArr[$sampleidArr[0]];
				$currentSpecies = $speciesArr[$sampleidArr[0]];
				$counter = 0;
				// Create class for checkAll/uncheckAll buttons
				$class = '';
				
				echo("\t\t<h1 class='top'>Check the samples of run ". $_SESSION['runnumber'] ."</h1>\n");
				echo("\t\t<h1>for which a result sent date has to be added</h1>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
				echo("\t\t\t<table>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'><span class='error'>". $dateErr ."</span></td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'></td>\n\t\t\t\t</tr>\n");
				for ($i = 0; $i < count($sampleidArr); $i++)
				{
					if (strcmp($currentDivision, $divisionArr[$sampleidArr[$i]]) == 0
						&& strcmp($currentProject, $projectArr[$sampleidArr[$i]]) == 0
						&& strcmp($currentSpecies, $speciesArr[$sampleidArr[$i]]) == 0)
					{
						$counter++;
						if ($counter == 1)
						{
							$class = $samplenameArr[$sampleidArr[$i]];
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
						echo("\t\t\t\t\t<td>\n\t\t\t\t\t\t<input class='samples ".$class."' type='checkbox' name=sample[] value='". $sampleidArr[$i] ."' checked />". $samplenameArr[$sampleidArr[$i]] ."\n\t\t\t\t\t</td>\n");
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
							$noSamples[] = $counter;
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Result sent date:</td>\n");
							echo("\t\t\t\t\t<td colspan='2'>\n\t\t\t\t\t\t<input type='text' name='date[]' placeholder='dd/mm/yyyy' pattern='[0-9]{2}/[0-9]{2}/20[0-9]{2}' title='e.g. 02/05/2017' />\n");
							echo("\t\t\t\t\t\t<span class='error'> </span>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
							echo("\t\t\t\t\t<td colspan='2'><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'>\n\t\t\t\t\t\t<input name='".$class."' class='checkbutton' type='button' value='Check all samples' />\n");
							echo("\t\t\t\t\t\t<input name='".$class."' class='uncheckbutton' type='button' value='Uncheck all samples' />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
						}
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'></td>\n\t\t\t\t</tr>\n");
						$currentDivision = $divisionArr[$sampleidArr[$i]];
						$currentProject = $projectArr[$sampleidArr[$i]];
						$currentSpecies = $speciesArr[$sampleidArr[$i]];
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
					$noSamples[] = $counter;
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Result sent date:</td>\n");
					echo("\t\t\t\t\t<td colspan='2'>\n\t\t\t\t\t\t<input type='text' name='date[]' placeholder='dd/mm/yyyy' pattern='[0-9]{2}/[0-9]{2}/20[0-9]{2}' title='e.g. 02/05/2017' />\n");
					echo("\t\t\t\t\t\t<span class='error'> </span>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
					echo("\t\t\t\t\t<td colspan='2'><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'>\n\t\t\t\t\t\t<input name='".$class."' class='checkbutton' type='button' value='Check all samples' />\n");
					echo("\t\t\t\t\t\t<input name='".$class."' class='uncheckbutton' type='button' value='Uncheck all samples' />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
				}
				echo("\t\t\t</table>\n");
				$_SESSION['noSamples'] = $noSamples;
				echo("\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
				echo("\t\t\t\t<a class='buttonMarginRight' href='addResultSentDateClear.php'>Clear</a>\n");
				echo("\t\t\t\t<input class='button' type='submit' name='next' value='Continue' />\n");
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