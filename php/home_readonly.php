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
	if(isset($_SESSION['role']) && strcmp($_SESSION['role'], 'readonly') == 0)
	{
		require("functions.php");
		createHead(true, 'MiQUBase home', ['home'], null);
		createHeader($_SESSION['user'], true);
?>
		<table>
			<tr>
				<td><a href="query.php">Query database</a></td>
			</tr>
			<tr>
				<td><a href="reportToRepeat.php">Report: samples to repeat</a></td>
			</tr>
			<tr>
				<td><a href="reportQc.php">Report: QC parameters</a></td>
			</tr>
		</table>
<?php
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