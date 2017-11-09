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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		// Unset post and session variables to clear the form
		if (isset($_POST['run'])) unset($_POST['run']);
		if (isset($_POST['laneid'])) unset($_POST['laneid']);
		if (isset($_POST['next'])) unset($_POST['next']);
		if (isset($_POST['sample'])) unset($_POST['sample']);
		if (isset($_POST['remark'])) unset($_POST['remark']);
		if (isset($_SESSION['runstartdate'])) unset($_SESSION['runstartdate']);
		if (isset($_SESSION['runnumber'])) unset($_SESSION['runnumber']);
		if (isset($_SESSION['sampleidArr'])) unset($_SESSION['sampleidArr']);
		if (isset($_SESSION['samplenameArr'])) unset($_SESSION['samplenameArr']);
		if (isset($_SESSION['isrepeatofArr'])) unset($_SESSION['isrepeatofArr']);
		if (isset($_SESSION['oldremarkArr'])) unset($_SESSION['oldremarkArr']);
		if (isset($_SESSION['projectArr'])) unset($_SESSION['projectArr']);
		if (isset($_SESSION['divisionArr'])) unset($_SESSION['divisionArr']);
		if (isset($_SESSION['speciesArr'])) unset($_SESSION['speciesArr']);
		if (isset($_SESSION['noSamples'])) unset($_SESSION['noSamples']);
		
		// Redirect user to the add result sent date form
		header("Location: addResultSentDate.php");
	}
	else 
	{
		// Session variable isn't registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>