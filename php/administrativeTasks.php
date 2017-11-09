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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require("functions.php");
		createHead(true, 'MiQUBase admin tasks', ['home'], null);
		createHeader($_SESSION['user'], true);
?>
		<table>
			<tr>
				<td><a href="addDivision.php">Add division</a></td>
				<td><a class="large" href="statusDivision.php">Activate / inactivate division</a></td>
			</tr>
			<tr>
				<td><a href="addIndexKit.php">Add indexKit</a></td>
				<td><a class="large" href="statusIndexKit.php">Activate / inactivate indexKit</a></td>
			</tr>
			<tr>
				<td><a href="addLibraryPrepkit.php">Add libraryPrepKit</a></td>
				<td><a class="large" href="statusLibraryPrepKit.php">Activate / inactivate libraryPrepKit</a></td>
			</tr>
			<tr>
				<td><a href="addProject.php">Add project</a></td>
				<td><a class="large" href="statusProject.php">Activate / inactivate project</a></td>
			</tr>
			<tr>
				<td><a href="addOrganism.php">Add organism</a></td>
				<td><a class="large" href="removeToRepeat.php">Remove 'to repeat' from sample</a></td>
			</tr>
			<tr>
				<td><a href="addSpecies.php">Add species</a></td>
				<td></td>
			</tr>
			<tr>
				<td colspan="2"><a class='back' href='home.php'>Back to home page</a></td>
			</tr>
		</table>
<?php
		createFooter(true);
	}
	else {
		// Session variable isn't registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>