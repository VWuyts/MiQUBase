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
		createHead(true, 'MiQUBase add samples', ['message'], null);
		createHeader($_SESSION['user'], true);
				
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			unset($_SESSION['inputArr']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Set error handler to transaction error handler
			set_error_handler('errorHandlerTransaction', E_ALL);
			// Start transaction to make sure that inserts can be rolled back
			$result = pg_query($dbconn, 'BEGIN TRANSACTION');
			if (!$result)
			{
				// Transaction rollback
				$result = pg_query($dbconn, 'ROLLBACK');
				pg_close($dbconn);
				unset($_SESSION['inputArr']);
				die();
			}
			// Insert lane array into database
			$result = pg_insert($dbconn, 'lane', $_SESSION['inputArr']['lane']);
			if (!$result)
			{
				// Transaction rollback
				$result = pg_query($dbconn, 'ROLLBACK');
				pg_close($dbconn);
				unset($_SESSION['inputArr']);
				die();
			}
			else
			{
				// Commit transaction
				$result = pg_query($dbconn, 'COMMIT');
				if (!$result)
				{
					// Transaction rollback
					$result = pg_query($dbconn, 'ROLLBACK');
					pg_close($dbconn);
					unset($_SESSION['inputArr']);
					die();
				}
				else
				{
					// Set error handler to default error handler
					set_error_handler('errorHandler', E_ALL);
					$query = "SELECT lane.laneid
							FROM lane
								LEFT JOIN run
								ON lane.runid = run.runid
							WHERE run.runid = $1";
					$result = pg_query_params($dbconn, $query, [$_SESSION['inputArr']['runId']]);
					if (!$result)
					{
						pg_close($dbconn);
						$errorlogger->error('lane added to database should be manually removed by administrator', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'file'=>__FILE__, 'line'=>__LINE__]);
						unset($_SESSION['inputArr']);
						trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					elseif (pg_num_rows($result) == 0)
					{
						pg_close($dbconn);
						$errorlogger->error('lane added to database should be manually removed by administrator', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'file'=>__FILE__, 'line'=>__LINE__]);
						unset($_SESSION['inputArr']);
						trigger_error('016@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						if (!($_SESSION['inputArr']['laneid'] = pg_fetch_result($result, 'laneid')))
						{
							pg_close($dbconn);
							$errorlogger->error('lane added to database should be manually removed by administrator', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'file'=>__FILE__, 'line'=>__LINE__]);
							unset($_SESSION['inputArr']);
							trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							// Create log message
							$activitylogger->info('adding lane to db succeeded', ['user'=>$_SESSION['user'], 'laneId'=>$_SESSION['inputArr']['laneid'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'runID'=>$_SESSION['inputArr']['runId']]);
							
							// Add samples, ngsReads and summaryTotal to database and update column organismId of table run
							// Set error handler to transaction error handler
							set_error_handler('errorHandlerTransaction', E_ALL);
							// Start transaction to make sure that inserts can be rolled back
							$result = pg_query($dbconn, 'BEGIN TRANSACTION');
							if (!$result)
							{
								// Transaction rollback
								$result = pg_query($dbconn, 'ROLLBACK');
								pg_close($dbconn);
								unset($_SESSION['inputArr']);
								die();
							}
							// Insert sample array into database
							for ($i = 0; $i < count($_SESSION['inputArr']['sampleArr']); $i++)
							{
								$_SESSION['inputArr']['sampleArr'][$i]['laneid'] = $_SESSION['inputArr']['laneid'];
								$result = pg_insert($dbconn, 'sample', $_SESSION['inputArr']['sampleArr'][$i]);
								if (!$result)
								{
									// Transaction rollback
									$result = pg_query($dbconn, 'ROLLBACK');
									pg_close($dbconn);
									$errorlogger->error('lane added to database should be manually removed by administrator', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'file'=>__FILE__, 'line'=>__LINE__]);
									unset($_SESSION['inputArr']);
									die();
								}
							}
							// insert ngsReads array into database
							for ($i = 0; $i < count($_SESSION['inputArr']['ngsReadArr']); $i++)
							{
								$_SESSION['inputArr']['ngsReadArr'][$i]['laneid'] = $_SESSION['inputArr']['laneid'];
								$result = pg_insert($dbconn, 'ngsread', $_SESSION['inputArr']['ngsReadArr'][$i]);
								if (!$result)
								{
									// Transaction rollback
									$result = pg_query($dbconn, 'ROLLBACK');
									pg_close($dbconn);
									$errorlogger->error('lane added to database should be manually removed by administrator', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'file'=>__FILE__, 'line'=>__LINE__]);
									unset($_SESSION['inputArr']);
									die();
								}
							}
							// insert summaryTotal array into database
							for ($i = 0; $i < count($_SESSION['inputArr']['summaryTotalArr']); $i++)
							{
								$_SESSION['inputArr']['summaryTotalArr'][$i]['laneid'] = $_SESSION['inputArr']['laneid'];
								$result = pg_insert($dbconn, 'summarytotal', $_SESSION['inputArr']['summaryTotalArr'][$i]);
								if (!$result)
								{
									// Transaction rollback
									$result = pg_query($dbconn, 'ROLLBACK');
									pg_close($dbconn);
									$errorlogger->error('lane added to database should be manually removed by administrator', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'file'=>__FILE__, 'line'=>__LINE__]);
									unset($_SESSION['inputArr']);
									die();
								}
							}
							// Update column organismId of table run
							// Create arrays to keep track of different values for speciesId and organismId
							$species = array();
							$species[0] = $_SESSION['inputArr']['sampleArr'][0]['speciesid'];
							$organism = array();
							for ($i = 1; $i < count($_SESSION['inputArr']['sampleArr']); $i++)
							{
								if ($_SESSION['inputArr']['sampleArr'][$i]['speciesid'] != $_SESSION['inputArr']['sampleArr'][$i-1]['speciesid'])
								{
									array_push($species, $_SESSION['inputArr']['sampleArr'][$i]['speciesid']);
								}
							}
							for ($i = 0; $i < count($species); $i++)
							{
								if ($i == 0 || $species[$i] != $species[$i - 1])
								{
									$query = "SELECT organismid
												FROM species
												WHERE speciesid = $1;";
									$result = pg_query_params($dbconn, $query, [$species[$i]]);
									if (!$result)
									{
										// Transaction rollback
										$result = pg_query($dbconn, 'ROLLBACK');
										pg_close($dbconn);
										unset($_SESSION['inputArr']);
										trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
										die();
									}
									else
									{
										if (!($organismid = pg_fetch_result($result, 'organismid')))
										{
											// Transaction rollback
											$result = pg_query($dbconn, 'ROLLBACK');
											pg_close($dbconn);
											unset($_SESSION['inputArr']);
											trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
											die();
										}
										else
										{
											if ($i == 0)
											{
												array_push($organism, $organismid);
											}
											else
											{
												if (array_search($organismid, $organism) === false)
												{
													array_push($organism, $organismid);
												}
											}
										} 
									}
								}
							}
							if (count($organism) > 1)
							{
								$organism = array();
								$query = "SELECT organismid
											FROM organism
											WHERE upper(organismname) = upper($1);";
								$result = pg_query_params($dbconn, $query, ['Mix']);
								if (!$result)
								{
									// Transaction rollback
									$result = pg_query($dbconn, 'ROLLBACK');
									pg_close($dbconn);
									unset($_SESSION['inputArr']);
									trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
									die();
								}
								else
								{
									if (!($organism[0] = pg_fetch_result($result, 'organismid')))
									{
										// Transaction rollback
										$result = pg_query($dbconn, 'ROLLBACK');
										pg_close($dbconn);
										unset($_SESSION['inputArr']);
										trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
										die();
									}
								}
							}
							$query = "UPDATE run
										SET organismid = $1
										WHERE runid = $2;";
							$result = pg_query_params($dbconn, $query, [$organism[0], $_SESSION['inputArr']['runId']]);
							if (!$result)
							{
								// Transaction rollback
								$result = pg_query($dbconn, 'ROLLBACK');
								pg_close($dbconn);
								unset($_SESSION['inputArr']);
								trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
								die();
							}
							// Commit transaction
							$result = pg_query($dbconn, 'COMMIT');
							if (!$result)
							{
								// Transaction rollback
								$result = pg_query($dbconn, 'ROLLBACK');
								pg_close($dbconn);
								unset($_SESSION['inputArr']);
								die();
							}
							// Set error handler to default error handler
							set_error_handler('errorHandler', E_ALL);
							// Create log message
							$activitylogger->info('adding samples, ngsReads and summaryTotal to db and updating organismId of run succeeded', ['user'=>$_SESSION['user'], 'laneId'=>$_SESSION['inputArr']['laneid'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'runID'=>$_SESSION['inputArr']['runId']]);
							echo("\t\t<p class='message'>Samples of run ". $_SESSION['inputArr']['runNumber'] ."</p>\n");
							echo("\t\t<p class='message'>have been added to MiQUBase.</p>\n");
							echo("\t\t<p><a href='home.php'>Back to home page</a></p>\n");
							pg_close($dbconn);
							unset($_SESSION['inputArr']);
						}
					}
				}
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