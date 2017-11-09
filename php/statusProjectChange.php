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
	if (isset($_SESSION['role']) && isset($_SESSION['input']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require("functions.php");
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase project', ['isActive'], null);
		createHeader($_SESSION['user'], true);
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Create array for updated projectid
			$idArr = array();
			// Perform required updates
			$query = "UPDATE project
						SET isactive = $1
						WHERE projectid = $2;";
			for ($i = 0; $i < count($_SESSION['input']['id']); $i++)
			{
				$params = array();
				if (isset($_POST[$_SESSION['input']['id'][$i]]))
				{
					unset($_POST[$_SESSION['input']['id'][$i]]);
					if (strcmp($_SESSION['input']['status'][$i], 'active') == 0)
					{
						$params[] = 'false';
					}
					else
					{
						$params[] = 'true';
					}
					$params[] = $_SESSION['input']['id'][$i];
					$result = pg_query_params($dbconn, $query, $params);
					if (!$result)
					{
						unset($_SESSION['input']);
						pg_close($dbconn);
						trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$idArr[] = $_SESSION['input']['id'][$i];
						// Create log message
						$activitylogger->info('status change of project succeeded', ['user'=>$_SESSION['user'], 'projectid'=>$_SESSION['input']['id'][$i], 'changedto'=>$params[0]]);
					}
				}
			}
			unset($_SESSION['input']);
			if (count($idArr) == 0)
			{
				echo("\t\t<p class='message'>No projects have changed their status.</p>\n");
			}
			else
			{
				$query = "SELECT projectnumber, isactive
							FROM project
							WHERE projectid = ". $idArr[0];
				for ($i = 1; $i < count($idArr); $i++)
				{
					$query .= " OR projectid = ".  $idArr[$i];
				}
				$query .= " ORDER BY projectnumber ASC;";
				$result = pg_query($dbconn, $query);
				if (!$result)
				{
					pg_close($dbconn);
					trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				else
				{
					echo("\t\t<h1>Projects of which the status is changed</h1>\n");
					echo("\t\t<table>\n");
					echo("\t\t\t<tr>\n\t\t\t\t<th>Project number</th>\n\t\t\t\t<th>Current status</th>\n\t\t\t</tr>\n");
					while($arr = pg_fetch_array($result))
					{
						$currentStatus = "active";
						if (strcmp($arr['isactive'], 'f') == 0)
						{
							$currentStatus = "not active";
						}
						echo("\t\t\t<tr>\n\t\t\t\t<td>".$arr['projectnumber']."</td>\n\t\t\t\t<td>".$currentStatus."</td>\n\t\t\t</tr>\n");
					}
					echo("\t\t</table>\n");
				}
			}
			echo("\t\t<div class='buttonbox'>\n");
			echo("\t\t\t<a class='right large' href='administrativeTasks.php'>Back to tasks overview page</a>\n");
			echo("\t\t\t<p class='spacer'></p>\n");
			echo("\t\t</div>\n");
		}
		createFooter(true);
	}
	else {
		// Session variable isn't registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>