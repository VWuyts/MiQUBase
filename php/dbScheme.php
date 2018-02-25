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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0
		|| strcmp($_SESSION['role'], 'readonly') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		createHead(true, 'MiQUBase query', ['query'], null);
		createHeader($_SESSION['user'], true);
		
		echo("\t\t<img id='dbscheme' src='../images/MiQUBase_schema.svg' alt='database scheme' />\n");
		echo("\t\t<form>\n\t\t\t<input id='close' type='button' value='Close' onclick='window.close();' />\n\t\t</form>\n");
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