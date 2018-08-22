<?php
/* Copyright (C) 2017-2018 Véronique Wuyts
 * student at Thomas More Mechelen-Antwerpen vzw -- Campus De Nayer
 * Professionele Bachelor Elektronica-ICT -- fase 1
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
	// Redirect user to login form if user is calling login.php directly.
	if (!isset($_POST['username']) || !isset($_POST['password']))
	{
		header("Location: ../index.php");
	}
	// Redirect user to login form if username or password field is empty.
	elseif (empty($_POST['username']) || empty($_POST['password']))
	{
		header("Location: ../index.php");
	}
	else
	{
		
		require 'functions.php';
		$user = strtolower($_POST['username']);
		$pass = hash('sha256', $_POST['password']);
		unset($_POST['username']);
		unset($_POST['password']);
		if(file_exists('../conf/config.ini'))
		{
			$config = parse_ini_file('../conf/config.ini');
			if(!$config)
			{
				createErrorPage(['Parsing the MiQUBase configuration file failed.', 'Please contact the MiQUBase administrator.']);
				// Errorlog is not possible here, as the configuration file has not yet been parsed
				die();
			}
			else
			{
				// Create string for database connection
				$connString = "host=".$config['host']." port=".$config['port']." dbname=".$config['dbname']." user=".$user." password=".$pass;
				// Set temporary configurations for logging
				$GLOBALS['activitylog'] = $config['activitylog'];
				$GLOBALS['errorlog'] = $config['errorlog'];
				$GLOBALS['maxfiles'] = $config['maxfiles'];
				require 'logHandling.php';
				require 'errorHandling.php';
				// Check all requirements for correct login
				if (($dbconn = pg_connect($connString)) === false)
				{
					trigger_error('001@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_WARNING);
					die();
				}
				else
				{
					// Check if user is active
					$query = "SELECT isactive
						FROM employee
						WHERE username = $1;";
					$result = pg_query_params($dbconn, $query, [$user]);
					if (!$result)
					{
						pg_close($dbconn);
						trigger_error('002@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_ERROR);
						die();
					}
					elseif (pg_fetch_result($result, 0, 0) == 'f')
					{
						pg_close($dbconn);
						trigger_error('003@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_WARNING);
						die();
					}
					else
					{
						// Get role for user
						$query = "SELECT pg_roles.rolname, pg_user.usename
							FROM pg_roles
								INNER JOIN pg_auth_members
								ON pg_roles.oid = pg_auth_members.roleid
								INNER JOIN pg_user
								ON pg_auth_members.member = pg_user.usesysid
							WHERE pg_user.usename = $1;";
						$result = pg_query_params($dbconn, $query, [$user]);
						if (!$result)
						{
							pg_close($dbconn);
							trigger_error('002@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_ERROR);
							die();
						}
						elseif (!($role = pg_fetch_result($result, 0, 0)))
						{
							pg_close($dbconn);
							trigger_error('004@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_ERROR);
							die();
						}
						else
						{
							// Get initials of user
							$query = "SELECT initials
								FROM employee
								WHERE username = $1;";
							$result = pg_query_params($dbconn, $query, [$user]);
							if (!$result)
							{
								pg_close($dbconn);
								trigger_error('002@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_ERROR);
								die();
							}
							elseif (!($initials = pg_fetch_result($result, 0, 0)))
							{
								pg_close($dbconn);
								trigger_error('004@'.$user.'@'.__FILE__.'@'.__LINE__.'@1', E_USER_ERROR);
								die();
							}
							else
							{
								// Session start
								session_start();
								// Register user variables
								$_SESSION['user'] = $user;
								$_SESSION['initials'] = $initials;
								$_SESSION['role'] = $role;
								$_SESSION['connString'] = $connString;
								// Set configurations
								$_SESSION['activitylog'] = $GLOBALS['activitylog'];
								$_SESSION['errorlog'] = $GLOBALS['errorlog'];
								$_SESSION['maxfiles'] = $GLOBALS['maxfiles'];
								$_SESSION['upload'] = $config['upload'];
								$_SESSION['months'] = 'P' . $config['months'] . 'M';
								// Create log message
								$activitylogger->info('login succeeded', ['user'=>$_SESSION['user']]);
								pg_close($dbconn);
								header("Location: home.php");
							}
						}
					}
				}
			}
		}
		else
		{
			createErrorPage(['The MiQUBase configuration file could not be found.', 'Please contact the MiQUBase administrator']);
			// Errorlog is not possible here, as the configuration file has not yet been parsed
			die();
		}
	}
?>