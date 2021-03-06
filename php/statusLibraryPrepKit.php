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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require("functions.php");
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase libraryPrepKit', ['isActive'], null);
		createHeader($_SESSION['user'], true);
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Create array for status values
			$libprepkitArr = [
				'status'	=> array(),
				'id'		=> array(),
			];
			// Overview of current status
			$query = "SELECT DISTINCT * FROM libraryprepkit ORDER BY libraryprepkitname ASC;";
			$result = pg_query($dbconn, $query);
			if (!$result)
			{
				pg_close($dbconn);
				trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (pg_num_rows($result) == 0)
			{
				echo("\t\t<p class='message'>There are currently no libraryPrepKits loaded into MiQUBase.</p>\n");
				echo("\t\t<p><a href='administrativeTasks.php'>Back to tasks overview page</a></p>\n");
			}
			else
			{
				echo("\t\t<h1>LibraryPrepKits loaded into MiQUBase</h1>\n");
				echo("\t\t<form method='post' action='statusLibraryPrepKitChange.php'>\n");
				echo("\t\t\t<table>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<th>LibraryPrepKit name</th>\n\t\t\t\t\t<th>Current status</th>\n\t\t\t\t\t<th>Change status?</th>\n\t\t\t\t</tr>\n");
				while($arr = pg_fetch_array($result))
				{
					$currentStatus = "active";
					if (strcmp($arr['isactive'], 'f') == 0)
					{
						$currentStatus = "not active";
					}
					$libprepkitArr['status'][] = $currentStatus;
					$libprepkitArr['id'][] = $arr['libraryprepkitid'];
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td>".$arr['libraryprepkitname']."</td>\n\t\t\t\t\t<td>".$currentStatus."</td>\n");
					echo("\t\t\t\t\t<td><input class='checkbox' type='checkbox' name='".$arr['libraryprepkitid']."' value='change'></td>\n");
					echo("\t\t\t\t</tr>\n");
				}
				$_SESSION['input'] = $libprepkitArr;
				echo("\t\t\t</table>\n");
				echo("\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<input class='button right' type='submit' value='Change status' />\n");
				echo("\t\t\t\t<a class='cancel right' href='administrativeTasks.php'>Cancel</a>\n");
				echo("\t\t\t\t<p class='spacer'></p>\n");
				echo("\t\t\t</div>\n\t\t</form>\n");
			}
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