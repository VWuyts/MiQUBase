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
	// Check required session variables
	if (isset($_SESSION['inputArr']) && isset($_SESSION['role'])
		&& (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		$script = "\t\t\tdocument.getElementById('uncheckbutton').onclick = uncheckAll;\n";
		$script .= "\t\t\tdocument.getElementById('checkbutton').onclick = checkAll;\n";
		$script .= "\t\t\tfunction uncheckAll(){\n";
		$script .= "\t\t\t\tvar checks = document.getElementsByClassName('samples');\n";
		$script .= "\t\t\t\tif (checks){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < checks.length; i++){\n";
		$script .= "\t\t\t\t\t\tchecks[i].checked = false;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n";
		$script .= "\t\t\tfunction checkAll(){\n";
		$script .= "\t\t\t\tvar checks = document.getElementsByClassName('samples');\n";
		$script .= "\t\t\t\tif (checks){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < checks.length; i++){\n";
		$script .= "\t\t\t\t\t\tchecks[i].checked = true;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}";
		createHead(true, 'MiQUBase sample reception date', ['actions'], $script);
		createHeader($_SESSION['user'], true);
			
		// Declare error variable and set to empty value
		$dateErr = "";
		if (isset($_POST['addDates']))
		{
			// Check user inputs
			if (empty($_POST['sample']))
			{
				$dateErr = "Required to check a sample";
			}
			else
			{
				// Check if there are no empty reception dates
				$inputOk = true;
				for ($i = 0; $i < count($_SESSION['inputArr']['noSamplesForDate']); $i++)
				{
					if (empty($_POST['date'][$i]))
					{
						$inputOk = false;
					}
				}
				if (!$inputOk)
				{
					$dateErr = "Required to set a date";
				}
				else
				{
					// Clear array for reception dates and remarks
					$_SESSION['inputArr']['receptionDates'] = array();
					$_SESSION['inputArr']['remarks'] = array();
					
					for ($i = 0; $i < count($_POST['date']); $i++)
					{
						$receptionDate = getValidDate(cleanInputText($_POST['date'][$i]));
						// Check if valid date
						if ($receptionDate === false)
						{
							$dateErr = "Date in format dd/mm/yyyy required";
							$receptionDate = null;
						}
						// Check if reception date is before run start date
						elseif (((date_diff(date_create($_SESSION['inputArr']['startDate']), date_create($receptionDate)))->format('%R%a')) > 0)
						{
							$dateErr = "Reception date should be before run start date";
							$receptionDate = null;
						}
						// Put reception date in array
						$minIndex = 0;
						$maxIndex = $_SESSION['inputArr']['noSamplesForDate'][$i];
						if ($i > 0)
						{
							$minIndex = $_SESSION['inputArr']['noSamplesForDate'][$i - 1];
							$maxIndex = $minIndex + $_SESSION['inputArr']['noSamplesForDate'][$i];
						}
						for ($j = $minIndex; $j < $maxIndex; $j++)
						{
							array_push($_SESSION['inputArr']['receptionDates'], $receptionDate);
							if (isset ($_POST['remark'][$i]) && !empty(cleanInputText($_POST['remark'][$i])))
							{
								array_push($_SESSION['inputArr']['remarks'], $_SESSION['initials'] ." ". date('d/m/Y') .": ".cleanInputText($_POST['remark'][$i]));
							}
							else
							{
								array_push($_SESSION['inputArr']['remarks'], null);
							}
						}
					}
					for ($i = 0; $i < count($_POST['sample']); $i++)
					{
						$index = array_search($_POST['sample'][$i], $_SESSION['inputArr']['samplename']);
						$_SESSION['inputArr']['sampleArr'][$index]['receptiondate'] = $_SESSION['inputArr']['receptionDates'][$i];
						if (strcmp($_SESSION['inputArr']['remarks'][$i], ' ') != 0)
						{
							$_SESSION['inputArr']['sampleArr'][$index]['remark'] = $_SESSION['inputArr']['remarks'][$i];
						}
						else
						{
							$_SESSION['inputArr']['sampleArr'][$index]['remark'] = null;
						}
					}
				}
			}
			//Check if reception date is set for all samples
			if (checkReceptionDate($_SESSION['inputArr']['sampleArr']))
			{
				unset($_POST['addDates']);
				unset($_POST['sample']);
				unset($_POST['date']);
				header("Location: addSampleRepeatOf.php");
				die();
			}
		}
		
		// Show overview of samples
		$currentDivision = $_SESSION['inputArr']['division'][0];
		$currentProject = $_SESSION['inputArr']['project'][0];
		$currentSpecies = $_SESSION['inputArr']['species'][0];
		$counter = 0;
		// Clear array to keep track of number of samples for which reception date inputs are valid
		$_SESSION['inputArr']['noSamplesForDate'] = array();
		echo("\t\t<h1>Add reception date for samples of run ". $_SESSION['inputArr']['runNumber'] ."</h1>\n");
		echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
		echo("\t\t\t<table>\n");
		echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
		echo("\t\t\t\t\t<td colspan='2'>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
		echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project:</td>\n");
		echo("\t\t\t\t\t<td colspan='2'>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
		echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
		echo("\t\t\t\t\t<td colspan='2'>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
		for ($i = 0; $i < count($_SESSION['inputArr']['sampleArr']); $i++)
		{
			if (strcmp($currentDivision, $_SESSION['inputArr']['division'][$i]) == 0
				&& strcmp($currentProject, $_SESSION['inputArr']['project'][$i]) == 0
				&& strcmp($currentSpecies, $_SESSION['inputArr']['species'][$i]) == 0)
			{
				if (empty($_SESSION['inputArr']['sampleArr'][$i]['receptiondate']))
				{
					$counter++;
					if ($counter == 1)
					{
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Samples:</td>\n");
					}
					elseif ($counter % 2 == 1)
					{
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n");
					}
					echo("\t\t\t\t\t<td><input class='samples' type='checkbox' name=sample[] value='". $_SESSION['inputArr']['sampleArr'][$i]['samplename'] ."' checked />". $_SESSION['inputArr']['sampleArr'][$i]['samplename'] ."</td>\n");
					if ($counter % 2 == 0)
					{
						echo("\t\t\t\t</tr>\n");
					}
				}
			}
			else
			{
				if ($counter % 2 != 0)
				{
					echo("\t\t\t\t\t<td>&nbsp;</td>\n\t\t\t\t</tr>\n");
				}
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Reception date:</td>\n");
				if ($counter == 0)
				{
					echo("\t\t\t\t\t<td colspan='2'> Ok for all samples</td>\n\t\t\t\t</tr>\n");
				}
				else
				{
					array_push($_SESSION['inputArr']['noSamplesForDate'], $counter);
					echo("\t\t\t\t\t<td colspan='2'>\n");
					echo("\t\t\t\t\t\t<input type='text' name='date[]' placeholder='dd/mm/yyyy' pattern='[0-9]{2}/[0-9]{2}/20[0-9]{2}' title='e.g. 02/05/2017' required />\n");
					echo("\t\t\t\t\t\t<span class='error'> *</span>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n\t\t\t\t\t<td colspan='2'>\n");
					echo("\t\t\t\t\t\t<span class='error'>". $dateErr ."</span>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
					echo("\t\t\t\t\t<td colspan='2'><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
				}
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='3'></td>\n\t\t\t\t</tr>\n");
				$currentDivision = $_SESSION['inputArr']['division'][$i];
				$currentProject = $_SESSION['inputArr']['project'][$i];
				$currentSpecies = $_SESSION['inputArr']['species'][$i];
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
				echo("\t\t\t\t\t<td colspan='2'>". $currentDivision ."</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Project unit:</td>\n");
				echo("\t\t\t\t\t<td colspan='2'>". $currentProject ."</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Species:</td>\n");
				echo("\t\t\t\t\t<td colspan='2'>". $currentSpecies ."</td>\n\t\t\t\t</tr>\n");
				$counter = 0;
				$i--;
			}
		}
		if ($counter % 2 != 0)
		{
			echo("\t\t\t\t\t<td>&nbsp;</td>\n\t\t\t\t</tr>\n");
		}
		echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Reception date:</td>\n");
		if ($counter == 0)
		{
			echo("\t\t\t\t\t<td colspan='2'> Ok for all samples</td>\n\t\t\t\t</tr>\n");
		}
		else
		{
			array_push($_SESSION['inputArr']['noSamplesForDate'], $counter);
			echo("\t\t\t\t\t<td colspan='2'>\n");
			echo("\t\t\t\t\t\t<input type='text' name='date[]' placeholder='dd/mm/yyyy' pattern='[0-9]{2}/[0-9]{2}/20[0-9]{2}' title='e.g. 02/05/2017' required />\n");
			echo("\t\t\t\t\t\t<span class='error'> *</span>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td></td>\n\t\t\t\t\t<td colspan='2'>\n");
			echo("\t\t\t\t\t\t<span class='error'>". $dateErr ."</span>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='attribute'>Remarks:</td>\n");
			echo("\t\t\t\t\t<td colspan='2'><textarea name='remark[]'> </textarea></td>\n\t\t\t\t</tr>\n");
		}
		echo("\t\t\t</table>\n");
	
		echo("\t\t\t<div class='buttonBox'>\n");
		echo("\t\t\t\t<input id='checkbutton' class='button buttonMarginRight' type='button' value='Check all samples' />\n");
		echo("\t\t\t\t<input id='uncheckbutton' class='button' type='button' value='Uncheck all samples' />\n");
		echo("\t\t\t</div>\n");
		echo("\t\t\t<div class='buttonBox'>\n");
		echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
		echo("\t\t\t\t<a class='buttonMarginRight' href='addSampleClear.php'>Clear</a>\n");
		echo("\t\t\t\t<input class='button' type='submit' name='addDates' value='Add dates' />\n");
		echo("\t\t\t</div>\n");
		echo("\t\t</form>\n");

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