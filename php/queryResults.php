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
	if (isset($_SESSION['role']) && isset($_SESSION['query']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0
		|| strcmp($_SESSION['role'], 'readonly') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase query', ['report'], null);
		createHeader($_SESSION['user'], true);
		
		// Print report
		if (isset($_POST['excel']))
		{
			//$_SESSION['report'] = $queryResults;
			$_SESSION['excel'] = true;
			unset($_POST['excel']);
			header("Location: queryPrint.php");
		}
		if (isset($_POST['pdf']))
		{
			//$_SESSION['report'] = $queryResults;
			$_SESSION['excel'] = false;
			unset($_POST['pdf']);
			header("Location: queryPrint.php");
		}
		// Unset session variable if back to home page
		if (isset($_POST['home']))
		{
			unset($_POST['home']);
			unset($_SESSION['query']);
			if (isset($_SESSION['report'])) unset($_SESSION['report']);
			header("Location: home.php");
			die();
		}
		
		// Unset session variables, except query
		if (isset($_SESSION['distinct'])) unset($_SESSION['distinct']);
		if (isset($_SESSION['dbtable'])) unset($_SESSION['dbtable']);
		if (isset($_SESSION['fields'])) unset($_SESSION['fields']);
		if (isset($_SESSION['fieldvalue'])) unset($_SESSION['fieldvalue']);
		if (isset($_SESSION['order'])) unset($_SESSION['order']);
		if (isset($_SESSION['orderby'])) unset($_SESSION['orderby']);
		if (isset($_SESSION['where'])) unset($_SESSION['where']);
		// Create log message
		$activitylogger->info('database query', ['user'=>$_SESSION['user'], 'query'=>$_SESSION['query']]);
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			unset($_SESSION['query']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Set error handler to query error handler
			set_error_handler('errorHandlerQuery', E_ALL);
			// Try query
			$result = pg_query($dbconn, $_SESSION['query']);
			if (!$result)
			{
				unset($_SESSION['query']);
				pg_close($dbconn);
				die();
			}
			elseif (($rows = pg_num_rows($result)) == 0)
			{
				// Set error handler to default error handler
				set_error_handler('errorHandler', E_ALL);
				unset($_SESSION['query']);
				echo("\t\t<p class='message'>Your query did not retrieve any results from the database.</p>\n");
				echo("\t\t<p><a href='home.php'>Back to home page</a></p>\n");
				pg_close($dbconn);
			}
			else
			{
				// Set error handler to default error handler
				set_error_handler('errorHandler', E_ALL);
				echo("\t\t<p class='message'>Your query retrieved ". $rows ." rows from the database.</p>\n");
				$queryResults = pg_fetch_all($result);
				if (!$queryResults)
				{
					pg_close($dbconn);
					trigger_error('020@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				$keys = array_keys($queryResults[0]);
				echo("\t\t<table>\n\t\t\t<tr>\n");
				for ($i = 0; $i < count($keys); $i++)
				{
					echo("\t\t\t\t<td><b>". $keys[$i] ."</b></td>\n");
				}
				echo("\t\t\t</tr>\n");
				for ($i = 0; $i < $rows; $i++)
				{
					echo("\t\t\t<tr>\n");
					for ($j = 0; $j < count($keys); $j++)
					{
						if (preg_match("/20[0-9]{2}-[0-9]{2}-[0-9]{2}/", $queryResults[$i][$keys[$j]]))
						{
							$queryResults[$i][$keys[$j]] = getEuroDate($queryResults[$i][$keys[$j]]);
						}
						if (strcmp($queryResults[$i][$keys[$j]], 't') == 0)
						{
							$queryResults[$i][$keys[$j]] = 'TRUE';
						}
						if (strcmp($queryResults[$i][$keys[$j]], 'f') == 0)
						{
							$queryResults[$i][$keys[$j]] = 'FALSE';
						}
						echo("\t\t\t\t<td>". $queryResults[$i][$keys[$j]] ."</td>\n");
					}
					echo("\t\t\t</tr>\n");
				}
				echo("\t\t</table>\n");
				$_SESSION['report'] = $queryResults;
				echo("\t\t<form method='post' action='". htmlspecialchars($_SERVER['PHP_SELF']) ."'>\n");
				echo("\t\t\t<div class='buttonBox'>\n");
				echo("\t\t\t\t<input class='button buttonMarginRight' type='submit' name='home' value='Back to home page' />\n");
				echo("\t\t\t\t<input class='button buttonMarginRight' type='submit' name='excel' value='Print to Excel' />\n");
				echo("\t\t\t\t<input class='button' type='submit' name='pdf' value='Print to pdf' />\n");
				echo("\t\t\t</div>\n\t\t</form>\n");
				pg_close($dbconn);
			}
		}

		createFooter(true);
	}
	else
	{
		// Session variables are not registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>