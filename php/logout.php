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
	// Check if a session variable is registered
	if(isset($_SESSION['role']))
	{
		require 'functions.php';
		require 'logHandling.php';
		// Create log message
		$activitylogger->info('logout succeeded', ['user'=>$_SESSION['user']]);
		session_unset();
		session_destroy();
		createHead(true, 'MiQUBase logout', null, null);
		createHeader(null, true);
?>
		<p class="message">You are logged out of MiQUBase.</p>
		<p class="message">The browser can be closed.</p>
<?php
	createFooter(true);	
	}
	else
	{
		//session variable isn't registered, user shouldn't be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>