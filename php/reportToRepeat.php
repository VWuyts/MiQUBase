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
			header("Location: reportToRepeatPrint.php");
		}
		if (isset($_POST['pdf']))
		{
			$_SESSION['excel'] = false;
			unset($_POST['pdf']);
			header("Location: reportToRepeatPrint.php");
		}
		// Unset session variable if back to home page
		if (isset($_POST['home']))
		{
			unset($_POST['home']);
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
			// Create arrays for sample data
			$sampleArr = array();
			$rows = 0;
			// Create query
			$query = "SELECT sample.samplename, sample.receptiondate,
							run.runnumber, run.startdate,
							division.divisionname
						FROM sample
							LEFT JOIN lane
							ON sample.laneid = lane.laneid
							LEFT JOIN run
							ON lane.runid = run.runid
							LEFT JOIN project
							ON sample.projectid = project.projectid
							LEFT JOIN division
							ON project.divisionid = division.divisionid
						WHERE sample.torepeat = $1
						ORDER BY run.runnumber, sample.samplename;";
			$result = pg_query_params($dbconn, $query, [true]);
			if (!$result)
			{
				pg_close($dbconn);
				trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (($rows = pg_num_rows($result)) == 0)
			{
				echo("\t\t<p class='message'>There are currently no samples to repeat.</p>\n");
				echo("\t\t<p><a href='home.php'>Back to home page</a></p>\n");
			}
			else
			{
				for ($i = 0; $i < $rows; $i++)
				{
					$data = pg_fetch_array($result, $i);
					array_push($sampleArr, $data);					
				}
				if (empty($sampleArr))
				{
					pg_close($dbconn);
					trigger_error('017@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				else
				{
					echo("\t\t<h1>Overview of samples to repeat</h1>\n");
					echo("\t\t<table>\n");
					echo("\t\t\t<tr>\n\t\t\t\t<th>Sample name</th>\n");
					echo("\t\t\t\t<th>Run number</th>\n");
					echo("\t\t\t\t<th>Run start date</th>\n");
					echo("\t\t\t\t<th>Division</th>\n\t\t\t</tr>\n");
					for ($i = 0; $i < count($sampleArr); $i++)
					{
						echo("\t\t\t<tr>\n\t\t\t\t<td>". $sampleArr[$i]['samplename'] ."</td>\n");
						echo("\t\t\t\t<td>". $sampleArr[$i]['runnumber'] ."</td>\n");
						echo("\t\t\t\t<td>". getEuroDate($sampleArr[$i]['startdate']) ."</td>\n");
						echo("\t\t\t\t<td>". $sampleArr[$i]['divisionname'] ."</td>\n\t\t\t</tr>\n");
					}
					echo("\t\t</table>\n");
					$_SESSION['report'] = $sampleArr;
					echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
					echo("\t\t\t<div class='buttonBox'>\n");
					echo("\t\t\t\t<input class='button buttonMarginRight' type='submit' name='home' value='Back to home page' />\n");
					echo("\t\t\t\t<input class='button buttonMarginRight' type='submit' name='excel' value='Print to Excel' />\n");
					echo("\t\t\t\t<input class='button' type='submit' name='pdf' value='Print to pdf' />\n");
					echo("\t\t\t</div>\n\t\t</form>\n");
				}
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