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
		createHead(true, 'MiQUBase run', ['actions'], null);
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
			// Check user inputs 
			if (isset($_POST['addToDb']) && !empty($_POST['runid']) && !empty(trim($_POST['remark'])))
			{
				$remark = $_SESSION['initials'] ." ". date('d/m/Y') .": " . cleanInputText($_POST['remark']);
				$newRemark = $remark;
				$runnumber = "";
				// Get the already registered remarks
				$query = "SELECT remark, runnumber FROM run where runid=$1;";
				$result = pg_query_params($dbconn, $query, [$_POST['runid']]);
				if (!$result)
				{
					unset($_POST['addToDb']);
					unset($_POST['runid']);
					unset($_POST['remark']);
					pg_close($dbconn);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				elseif (pg_num_rows($result) > 0)
				{
					
					$oldRemark = trim(pg_fetch_result($result, 0, 'remark'));
					$runnumber = pg_fetch_result($result, 0, 'runnumber');
					if ($oldRemark === false || $runnumber === false)
					{
						unset($_POST['addToDb']);
						unset($_POST['runid']);
						unset($_POST['remark']);
						pg_close($dbconn);
						trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					elseif (!empty($oldRemark))
					{
						$remark = $oldRemark ." - " .$newRemark;
					}
				}
				// Add remark to database
				$query = "UPDATE run
							SET remark=$1
							WHERE runid= $2;";
				$result = pg_query_params($dbconn, $query, [$remark, $_POST['runid']]);
				if (!$result)
				{
					unset($_POST['addToDb']);
					unset($_POST['runid']);
					unset($_POST['remark']);
					pg_close($dbconn);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				else
				{
					// Create log message
					$activitylogger->info('adding remark to run in db succeeded', ['user'=>$_SESSION['user'], 'runid'=>$_POST['runid'], 'remark'=>$newRemark]);
					unset($_POST['addToDb']);
					unset($_POST['runid']);
					unset($_POST['remark']);
					pg_close($dbconn);
					// Redirect user to message page
					createMessagePage(["Remark '". $newRemark ."'", "has been added to run ".$runnumber, "in MiQUBase."],
						$_SESSION['user'], "../php/home.php", "Back home page");
					die();
				}
			}
			// Create date variable to limit the number of retrieved runs to those within the last number of months
			// as specified in de configuration
			$date = (date_sub(date_create(date('Y-m-d')), new DateInterval($_SESSION['months'])))->format('Y-m-d');
			// Run number
			$query = "SELECT runid, runnumber, remark
						FROM run
						WHERE startdate >= '".$date
						."' ORDER BY runnumber DESC;";
			$result = pg_query($dbconn, $query);
			if (!$result)
			{
				pg_close($dbconn);
				trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (($rows = pg_num_rows($result)) == 0)
			{
				echo("\t\t<p class='message'>There are currently no runs loaded into MiQUBase.</p>\n");
				echo("\t\t<p><a class='homelink' href='home.php'>Back to home page</a></p>\n");
			}
			else
			{
				echo("\t\t<h1>Add remarks to a run in MiQUBase</h1>\n");
				echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
				echo("\t\t\t<p>\n\t\t\t\t<label for='runid'>Run number:</label>\n");
				echo("\t\t\t\t<select id='runid' class='marginRight' name='runid' size='1' autofocus>\n");
				while($arr = pg_fetch_array($result))
				{
					echo("\t\t\t\t\t<option value=".$arr['runid'].">". $arr['runnumber'] ."</option>\n");
				}
				echo("\t\t\t\t</select>\n\t\t\t</p>\n");
				echo("\t\t\t<p>\n\t\t\t\t<label for='remark'>Remarks:</label>\n");
				echo("\t\t\t\t<textarea id='remark' name='remark'></textarea>\n\t\t\t</p>\n");
				echo("\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
				echo("\t\t\t\t<input class='button buttonMarginRight' type='reset' value='Clear' />\n");
				echo("\t\t\t\t<input class='button' type='submit' name='addToDb' value='Add to MiQUBase' />\n");
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