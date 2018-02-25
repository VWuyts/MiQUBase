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
	/*
	 * This php script sets up error handling and requires that 
	 * the Monolog loggers are already configured.
	 */

	require 'logHandling.php';

	/* Set error reporting */
	error_reporting(E_ALL);
	ini_set('display_errors', true); // development: true - production: false
	
	/*
	 * Create error handler for caught E_WARNINGs emitted by PHP or for user triggered error conditions (E_USER_ERROR, E_USER_WARNING or E_USER_NOTICE).
	 * Based on the $errstr ($err_msg in function trigger_error), the error is handled differently.
	 */
	function errorHandler($errno, $errstr, $errfile, $errline)
	{
		global $activitylogger, $errorlogger;
		$messageArray = explode('@', $errstr);
		$userMessage;
		// Log error
		switch($messageArray[0])
		{
			case '001':
				$activitylogger->notice('failed login', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Login to MiQUBase failed.', 'Please check your username and password.'];
				break;
			case '002':
				$errorlogger->error('php function pg_query_params failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Login to MiQUBase failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '003':
				$activitylogger->warning('login attempt with inactive account', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Your account is locked.', 'Please contact the MiQUBase administrator.'];
				break;
			case '004':
				$errorlogger->error('php function pg_fetch_result failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Login to MiQUBase failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '005':
				$errorlogger->error('connection to database failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Connection to the database failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '006':
				$errorlogger->error('php function pg_query_params failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Database query failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '007':
				$errorlogger->error('php function simplexml_load_file failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				for ($i = 5; $i < count($messageArray); $i++)
				{
					$errorlogger->error('error emitted by php function simplexml_load_file', ['errstr'=>$messageArray[$i]]);
				}
				$userMessage = ['Parsing xml file failed.', 'Please check the uploaded xml file.'];
				break;
			case '008':
				$errorlogger->error('php function pg_fetch_result failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Fetching a database result failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '009':
				$errorlogger->error('php function pg_query failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Database query failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '010':
				$activitylogger->notice('no runs to which samples can be added', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['There are no runs to which samples can be added.'];
				break;
			case '011':
				$errorlogger->error('required field is empty in Request FORM', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['A required value is empty in the Request FORM.', 'Please check the Request FORM you have uploaded.'];
				break;
			case '012':
				$errorlogger->error('required field is not valid in Request FORM', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['A required value is not valid in the Request FORM.', 'Please check the Request FORM you have uploaded.'];
				break;
			case '013':
				$errorlogger->error('required field is empty in SAV index tab', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['A required value is empty in the copy of the SAV index tab.', 'Please check the Excel file you have uploaded.'];
				break;
			case '014':
				$errorlogger->error('sample name in SAV index tab does not correspond to sample name in Request FORM', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['A sample name in the copy of the SAV index tab does not correspond to a sample name in the Request FORM.', 'Please check the Excel files you have uploaded.'];
				break;
			case '015':
				$errorlogger->error('required field is empty in SAV summary tab', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['A required value is empty in the copy of the SAV summary tab.', 'Please check the Excel file you have uploaded.'];
				break;
			case '016':
				$errorlogger->error('database did not return laneId', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Adding samples to run in database failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '017':
				$errorlogger->error('php function pg_fetch_array failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Fetching a database result failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '018':
				$errorlogger->error('setup of objPHPExcel for pdf writing failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3], 'rendererLibraryPath'=>$messageArray[5]]);
				$userMessage = ['Setup of pdf writer failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '019':
				$errorlogger->error('number of samples in SAV index tab does not equal the number of samples in Request FORM', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['The number of samples in the copy of the SAV index tab does not equal the number of samples in the Request FORM.', 'Please check the Excel copy of the SAV index tab you have uploaded.'];
				break;
			case '020':
				$errorlogger->error('php function pg_fetch_all failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Fetching a database result failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '021':
				$errorlogger->error('php function pg_fetch_row failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Fetching a database result failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '022':
				$errorlogger->error('php function pg_insert failed', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3]]);
				$userMessage = ['Inserting a database record failed.', 'Please contact the MiQUBase administrator.'];
				break;
			case '023':
				$activitylogger->warning('runID already present in database', ['user'=>$messageArray[1], 'errno'=>$messageArray[0], 'file'=>$messageArray[2], 'line'=>$messageArray[3], 'runID'=>$messageArray[5]]);
				$userMessage = [$messageArray[5] .' is already present in the database.', 'Duplicate runIDs are not allowed.'];
				break;
			default:
				if (isset($_SESSION['user']))
				{
					$errorlogger->error('caught E_WARNING', ['user'=>$_SESSION['user'], 'errno'=>$errno, 'errstr'=>$messageArray[0], 'errfile'=>$errfile, 'errline'=>$errline]);
				}
				else
				{
					$errorlogger->error('caught E_WARNING', ['errno'=>$errno, 'errstr'=>$messageArray[0], 'errfile'=>$errfile, 'errline'=>$errline]);
				}
				$userMessage = ['An unexpected error has occurred.'];
				break;
		}
		if (!isset($messageArray[4]) || (isset($messageArray[4]) && $messageArray[4] == 1))
		{
			// Clear session
			$activitylogger->info('logout after warning/error', ['user'=>$_SESSION['user']]);
			session_unset();
			session_destroy();
		}	
		// Redirect user to error page
		createErrorPage($userMessage);
		//bypass the PHP error handler
		return true;
	}
	
	/*
	 * Create error handler for caught E_WARNINGs emitted by PHP when adding data to the database in transactions.
	 * Transaction rollback has to be handled in the script where the error occurred, as $dbconn is needed.
	 */
	function errorHandlerTransaction($errno, $errstr, $errfile, $errline)
	{
		global $activitylogger, $errorlogger;
		// log error
		$errorlogger->error('caught E_WARNING @ database transaction', ['errno'=>$errno, 'errstr'=>$errstr, 'errfile'=>$errfile, 'errline'=>$errline]);
		// Redirect user to error page
		createErrorPage(['Database transaction failed.', 'Please contact the MiQUBase administrator.']);
		//bypass the PHP error handler
		return true;
	}
	 
	/*
	 * Create error handler for caught E_WARNINGs emitted by PHP when querying the database.
	 */
	function errorHandlerQuery($errno, $errstr, $errfile, $errline)
	{
		global $activitylogger, $errorlogger;
		// log error
		$activitylogger->warning('caught E_WARNING @ database query', ['errno'=>$errno, 'errstr'=>$errstr, 'errfile'=>$errfile, 'errline'=>$errline]);
		// Redirect user to error page
		createErrorPage(['Your query was not valid.']);
		//bypass the PHP error handler
		return true;
	}

	/*
	 * Create exception handler for uncaught errors/exceptions thrown by PHP.
	 */
	function exceptionHandler(Throwable $ex)
	{
		global $errorlogger;
		// Log error
		if (isset($_SESSION['user']))
		{
			$errorlogger->error('uncaught error', ['user'=>$_SESSION['user'], 'errno'=>$ex->getCode(), 'errstr'=>$ex->getMessage(), 'errfile'=>$ex->getFile(), 'errline'=>$ex->getLine()]);
		}
		else
		{
			$errorlogger->error('uncaught error', ['errno'=>$ex->getCode(), 'errstr'=>$ex->getMessage(), 'errfile'=>$ex->getFile(), 'errline'=>$ex->getLine()]);
		}
		// Redirect user to error page
		createErrorPage(['An unexpected error has occurred.', 'Please contact the MiQUBase administrator.']);
	}

	/*
	 * Set the default error handlers
	 */
	set_error_handler('errorHandler', E_ALL); // allow errors at levels E-WARNING, E_USER_ERROR, E_USER_WARNING and E_USER_NOTICE to be caught
	set_exception_handler('exceptionHandler'); // catch uncaught errors
?>