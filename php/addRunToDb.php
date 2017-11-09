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
		&& (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 ||
			strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase run overview', ['message'], null);
		createHeader($_SESSION['user'], true);
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			if (!unlink($_SESSION['inputArr']['runParXml']))
			{
				$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
			}
			unset($_SESSION['inputArr']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Create array with inputs in correct order
			$insertArr = [
				'runid'							=> $_SESSION['inputArr']['runId'],
				'runnumber'						=> $_SESSION['inputArr']['runNumber'],
				'instrumentsn'					=> $_SESSION['inputArr']['instrumentSn'],
				'startdate'						=> $_SESSION['inputArr']['startDate'],
				'libraryprepkitid'				=> $_SESSION['inputArr']['libPrep'],
				'pooledlibraryconcentration'	=> $_SESSION['inputArr']['libConc'],
				'loadingconcentration'			=> $_SESSION['inputArr']['loadConc'],
				'kapaquantification'			=> $_SESSION['inputArr']['kapa'],
				'indexkitid'					=> $_SESSION['inputArr']['indexKit'],
				'numcycles'						=> $_SESSION['inputArr']['numCycles'],
				'sequencingcartridge'			=> $_SESSION['inputArr']['seqCartidge'],
				'remark'						=> $_SESSION['inputArr']['remark'],
			];
			// Set error handler to transaction error handler
			set_error_handler('errorHandlerTransaction', E_ALL);
			// Start transaction to make sure that inserts in both tables run and runEmployee are committed
			$result = pg_query($dbconn, 'BEGIN TRANSACTION');
			if (!$result)
			{
				// Transaction rollback
				$result = pg_query($dbconn, 'ROLLBACK');
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				die();
			}
			// Insert array into database
			$result = pg_insert($dbconn, 'run', $insertArr);
			if (!$result)
			{
				// Transaction rollback
				$result = pg_query($dbconn, 'ROLLBACK');
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				die();
			}
			else
			{
				unset($insertArr);
				for($i = 0; $i < count($_SESSION['inputArr']['operator']); $i++)
				{
					$insertArr = [
						'runid'			=> $_SESSION['inputArr']['runId'],
						'employeeid'	=> $_SESSION['inputArr']['operator'][$i],
					];
					$result = pg_insert($dbconn, 'runemployee', $insertArr);
					if (!$result)
					{
						// Transaction rollback
						$result = pg_query($dbconn, 'ROLLBACK');
						pg_close($dbconn);
						unset($_SESSION['inputArr']);
						die();
					}
				}
				// Commit transaction
				$result = pg_query($dbconn, 'COMMIT');
				if (!$result)
				{
					// Transaction rollback
					$result = pg_query($dbconn, 'ROLLBACK');
					pg_close($dbconn);
					if (!unlink($_SESSION['inputArr']['runParXml']))
					{
						$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
					}
					unset($_SESSION['inputArr']);
					die();
				}
				// Set error handler to default error handler
				set_error_handler('errorHandler', E_ALL);
				// Create log message
				$activitylogger->info('adding run to db succeeded', ['user'=>$_SESSION['user'], 'runNumber'=>$_SESSION['inputArr']['runNumber'], 'runID'=>$_SESSION['inputArr']['runId']]);
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				echo("\t\t<p class='message'>Run ". $_SESSION['inputArr']['runNumber'] ."</p>\n");
				echo("\t\t<p class='message'>has been added to MiQUBase.</p>\n");
				echo("\t\t<p><a href='home.php'>Back to home page</a></p>\n");
				echo("\t\t<p><a href='addSample.php'>Add samples to run</a></p>\n");
				unset($_SESSION['inputArr']);
			}
			createFooter(true);
		}
	}
	else
	{
		// Session variables are not registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>