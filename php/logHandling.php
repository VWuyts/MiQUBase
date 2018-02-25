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
	spl_autoload_register(function ($class) {
		//$file = 'externalclasses/'.$class.'.php'; // for Windows environment
		$file = 'externalclasses/'.strtr($class, '\\', DIRECTORY_SEPARATOR).'.php'; // for Linux environment
		if (file_exists($file)) {
			require $file;
			return true;
		}
	});
	use Monolog\Logger;
	use Monolog\Handler\RotatingFileHandler;
	
	// Create the loggers
	$activitylogger = new Logger('activityLogger');
	$errorlogger = new Logger('errorLogger');
	// Add the handlers
	if (isset($GLOBALS['activitylog']) && isset($GLOBALS['errorlog']) && isset($GLOBALS['maxfiles']))
	{
		$activitylogger->pushHandler(new RotatingFileHandler($GLOBALS['activitylog'].'/activity.log', $GLOBALS['maxfiles'], Logger::INFO, false));
		$errorlogger->pushHandler(new RotatingFileHandler($GLOBALS['errorlog'].'/error.log', $GLOBALS['maxfiles'], Logger::ERROR, false));
	}
	elseif (isset($_SESSION['activitylog']) && isset($_SESSION['errorlog']) && isset($_SESSION['maxfiles']))
	{
		$activitylogger->pushHandler(new RotatingFileHandler($_SESSION['activitylog'].'/activity.log', $_SESSION['maxfiles'], Logger::INFO, false));
		$errorlogger->pushHandler(new RotatingFileHandler($_SESSION['errorlog'].'/error.log', $_SESSION['maxfiles'], Logger::ERROR, false));
	}
	else
	{
		// Redirect user to login form if required session variables are not set.
		header("Location: ../index.php");
	}
?>