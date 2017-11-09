<?php
/* Copyright (C) 2017 Véronique Wuyts
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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0)
		&& isset($_SESSION['runnumber']) && isset($_SESSION['samplenameArr']) && isset($_SESSION['isrepeatofArr']) && isset($_SESSION['projectArr'])
		&& isset($_SESSION['divisionArr']) && isset($_SESSION['speciesArr']) && isset($_SESSION['keys']))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		$script = "\t\t\tdocument.getElementById('uncheckbutton').onclick = uncheckAll;\n";
		$script .= "\t\t\tdocument.getElementById('checkbutton').onclick = checkAll;\n";
		$script .= "\t\t\tfunction uncheckAll(){\n";
		$script .= "\t\t\t\tvar no = document.getElementsByClassName('no');\n";
		$script .= "\t\t\t\tif (no){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < no.length; i++){\n";
		$script .= "\t\t\t\t\t\tno[i].checked = true;\n\t\t\t\t}\n\t\t\t}\n\t\t}";
		$script .= "\t\t\tfunction checkAll(){\n";
		$script .= "\t\t\t\tvar yes = document.getElementsByClassName('yes');\n";
		$script .= "\t\t\t\tif (yes){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < yes.length; i++){\n";
		$script .= "\t\t\t\t\t\tyes[i].checked = true;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}";
		createHead(true, 'MiQUBase sample', ['actions'], $script);
		createHeader($_SESSION['user'], true);
		
		// Create connection to database
		$dbconn = false;
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			unset($_SESSION['runnumber']);
			unset($_SESSION['samplenameArr']);
			unset($_SESSION['isrepeatofArr']);
			unset($_SESSION['projectArr']);
			unset($_SESSION['divisionArr']);
			unset($_SESSION['speciesArr']);
			unset($_SESSION['keys']);
			if (isset($_SESSION['repeatSampleidArr'])) unset($_SESSION['repeatSampleidArr']);
			if (isset($_SESSION['repeatedSampleidArr'])) unset($_SESSION['repeatedSampleidArr']);
			if (isset($_SESSION['repeatedRunnumberArr'])) unset($_SESSION['repeatedRunnumberArr']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Action for cancel button
			if (isset($_POST['noRemoveFlag']))
			{
				unset($_SESSION['samplenameArr']);
				unset($_SESSION['isrepeatofArr']);
				unset($_SESSION['projectArr']);
				unset($_SESSION['divisionArr']);
				unset($_SESSION['speciesArr']);
				unset($_SESSION['repeatSampleidArr']);
				unset($_SESSION['repeatedSampleidArr']);
				unset($_SESSION['repeatedRunnumberArr']);
				// Redirect user to message page
				createMessagePage(["For ". count($_SESSION['keys']) ." samples of run ".$_SESSION['runnumber'], "a result sent date has been added in MiQUBase.",
				"No flags 'to repeat' have been removed in MiQUBase"],
					$_SESSION['user'], "../php/home.php", "Back to home page");
				unset($_SESSION['runnumber']);
				unset($_SESSION['keys']);
				die();
			}
			// Action for continue button
			if (isset($_POST['removeFlag']))
			{
				$updateCounter = 0;
				$query = "UPDATE sample
							SET torepeat = FALSE
							WHERE sampleid = $1;";
				for ($i = 0; $i < count($_SESSION['repeatedSampleidArr']); $i++)
				{
					if (strcmp($_POST[$_SESSION['repeatedSampleidArr'][$i]], 'yes') == 0)
					{
						$updateCounter++;
						$result = pg_query_params($dbconn, $query, [$_SESSION['repeatedSampleidArr'][$i]]);
						if (!$result)
						{
							unset($_SESSION['runnumber']);
							unset($_SESSION['samplenameArr']);
							unset($_SESSION['isrepeatofArr']);
							unset($_SESSION['projectArr']);
							unset($_SESSION['divisionArr']);
							unset($_SESSION['speciesArr']);
							unset($_SESSION['keys']);
							unset($_SESSION['repeatSampleidArr']);
							unset($_SESSION['repeatedSampleidArr']);
							unset($_SESSION['repeatedRunnumberArr']);
							pg_close($dbconn);
							trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							// Create log message
							$activitylogger->info('set torepeat=false in db succeeded', ['user'=>$_SESSION['user'], 'runnumber'=>$_SESSION['repeatedRunnumberArr'][$i] , 'sampleid'=>$_SESSION['repeatedSampleidArr'][$i]]);
						}
					}
				}
				unset($_SESSION['samplenameArr']);
				unset($_SESSION['isrepeatofArr']);
				unset($_SESSION['projectArr']);
				unset($_SESSION['divisionArr']);
				unset($_SESSION['speciesArr']);
				unset($_SESSION['repeatSampleidArr']);
				unset($_SESSION['repeatedSampleidArr']);
				unset($_SESSION['repeatedRunnumberArr']);
				// Redirect user to message page
				createMessagePage(["For ". count($_SESSION['keys']) ." samples of run ".$_SESSION['runnumber'], "a result sent date has been added in MiQUBase.",
				"For ". $updateCounter ." samples, the flag 'to repeat' has been removed in MiQUBase"],
					$_SESSION['user'], "../php/home.php", "Back to home page");
				unset($_SESSION['runnumber']);
				unset($_SESSION['keys']);
				die();
			}
			
			// Create arrays to collect query results
			$repeatSampleidArr = array();
			$repeatedSampleidArr = array();
			$repeatedRunnumberArr = array();			
			// Get data on repeated samples
			$query = "SELECT sample.sampleid, sample.samplename, run.runnumber
						FROM sample
							LEFT JOIN lane
							ON sample.laneid = lane.laneid
							LEFT JOIN run
							ON lane.runid = run.runid
						WHERE sample.laneid = $1
							AND upper(sample.samplename) = upper($2)
							AND torepeat = TRUE
						ORDER BY run.runnumber, sample.samplename ASC;";
			for ($i = 0; $i < count($_SESSION['keys']); $i++)
			{
				$result = pg_query_params($dbconn, $query, [$_SESSION['isrepeatofArr'][$_SESSION['keys'][$i]], $_SESSION['samplenameArr'][$_SESSION['keys'][$i]]]);
				if (!$result)
				{
					unset($_SESSION['runnumber']);
					unset($_SESSION['samplenameArr']);
					unset($_SESSION['isrepeatofArr']);
					unset($_SESSION['projectArr']);
					unset($_SESSION['divisionArr']);
					unset($_SESSION['speciesArr']);
					unset($_SESSION['keys']);
					if (isset($_SESSION['repeatSampleidArr'])) unset($_SESSION['repeatSampleidArr']);
					if (isset($_SESSION['repeatedSampleidArr'])) unset($_SESSION['repeatedSampleidArr']);
					if (isset($_SESSION['repeatedRunnumberArr'])) unset($_SESSION['repeatedRunnumberArr']);
					pg_close($dbconn);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				elseif (pg_num_rows($result) > 0)
				{
					$arr = pg_fetch_array($result);
					if (!$arr)
					{
						unset($_SESSION['runnumber']);
						unset($_SESSION['samplenameArr']);
						unset($_SESSION['isrepeatofArr']);
						unset($_SESSION['projectArr']);
						unset($_SESSION['divisionArr']);
						unset($_SESSION['speciesArr']);
						unset($_SESSION['keys']);
						if (isset($_SESSION['repeatSampleidArr'])) unset($_SESSION['repeatSampleidArr']);
						if (isset($_SESSION['repeatedSampleidArr'])) unset($_SESSION['repeatedSampleidArr']);
						if (isset($_SESSION['repeatedRunnumberArr'])) unset($_SESSION['repeatedRunnumberArr']);
						pg_close($dbconn);
						trigger_error('017@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$repeatSampleidArr[] = $_SESSION['keys'][$i];
						$repeatedSampleidArr[] = $arr['sampleid'];
						$repeatedRunnumberArr[] = $arr['runnumber'];
					}
				}	
			}
			// Set session variables
			$_SESSION['repeatSampleidArr'] = $repeatSampleidArr;
			$_SESSION['repeatedSampleidArr'] = $repeatedSampleidArr;
			$_SESSION['repeatedRunnumberArr'] = $repeatedRunnumberArr;
			
			// Show overview of samples which are repeats
			if (!empty($repeatedSampleidArr))
			{
				$currentDivision = $_SESSION['divisionArr'][$repeatSampleidArr[0]];
				$currentProject = $_SESSION['projectArr'][$repeatSampleidArr[0]];
				$currentSpecies = $_SESSION['speciesArr'][$repeatSampleidArr[0]];
				echo("\t\t<h1>Following samples of run ". $_SESSION['runnumber'] ." are repeats</h1>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
				echo("\t\t\t<table>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
				echo("\t\t\t\t\t<td colspan='2'>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project:</td>\n");
				echo("\t\t\t\t\t<td colspan='2'>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
				echo("\t\t\t\t\t<td colspan='2'>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td>Sample</td>\n\t\t\t\t\t<td>Is repeat of run</td>\n\t\t\t\t\t<td>Remove flag 'to repeat'?</td>\n");
				for ($i = 0; $i < count($repeatSampleidArr); $i++)
				{
					if (strcmp($currentDivision, $_SESSION['divisionArr'][$_SESSION['keys'][$i]]) == 0
						&& strcmp($currentProject, $_SESSION['projectArr'][$_SESSION['keys'][$i]]) == 0
						&& strcmp($currentSpecies, $_SESSION['speciesArr'][$_SESSION['keys'][$i]]) == 0)
					{
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td>".$_SESSION['samplenameArr'][$repeatSampleidArr[$i]]."</td>\n");
						echo("\t\t\t\t\t<td>". $repeatedRunnumberArr[$i] ."</td>\n");
						echo("\t\t\t\t\t<td>\n\t\t\t\t\t\t<input class='yes samples' type='radio' name='". $repeatedSampleidArr[$i] ."' value='yes' checked />Yes\n");
						echo("\t\t\t\t\t\t<input class='no samples' type='radio' name='". $repeatedSampleidArr[$i] ."' value='no' />No\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
					}
					else
					{
						$currentDivision = $_SESSION['divisionArr'][$repeatSampleidArr[$i]];
						$currentProject = $_SESSION['projectArr'][$repeatSampleidArr[$i]];
						$currentSpecies = $_SESSION['speciesArr'][$repeatSampleidArr[$i]];
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'></td>\n\t\t\t\t</tr>\n");
						echo("\t\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
						echo("\t\t\t\t\t<td colspan='2'>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project:</td>\n");
						echo("\t\t\t\t\t<td colspan='2'>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
						echo("\t\t\t\t\t<td colspan='2'>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td>Sample</td>\n\t\t\t\t\t<td>Is repeat of</td>\n\t\t\t\t\t<td>Remove flag 'to repeat'?</td>\n");
						$i--;
					}
				}
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'></td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n\t\t\t\t\t<td colspan='2'>\n\t\t\t\t\t\t<input id='uncheckbutton' class='uncheckbutton' type='button' value='No for all samples' />\n");
				echo("\t\t\t\t\t\t<input id='checkbutton' class='checkbutton' type='button' value='Yes for all samples' />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t</table>\n");
				echo("\t\t\t<p class='spacer'></p>\n");
				echo("\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<input class='button right' type='submit' name='removeFlag' value='Continue' />\n");
				echo("\t\t\t\t<input class='button right' type='submit' name='noRemoveFlag' value='Cancel' />\n");
				echo("\t\t\t\t<p class='spacer'></p>\n");
				echo("\t\t\t</div>\n\t\t</form>\n");
			}
			else
			{
				unset($_SESSION['samplenameArr']);
				unset($_SESSION['isrepeatofArr']);
				unset($_SESSION['projectArr']);
				unset($_SESSION['divisionArr']);
				unset($_SESSION['speciesArr']);
				unset($_SESSION['keys']);
				unset($_SESSION['repeatSampleidArr']);
				unset($_SESSION['repeatedSampleidArr']);
				unset($_SESSION['repeatedRunnumberArr']);
				// Redirect user to message page
				createMessagePage(["For ". count($_SESSION['keys']) ." samples of run ".$_SESSION['runnumber'], "a result sent date has been added in MiQUBase."],
					$_SESSION['user'], "../php/home.php", "Back to home page");
				unset($_SESSION['runnumber']);
				unset($_SESSION['keys']);
				die();
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