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
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require("functions.php");
		createHead(true, 'MiQUBase reports', ['home'], null);
		createHeader($_SESSION['user'], true);
?>
		<table>
			<tr>
                <td><a href="#">Results to send</a></td> <!--TODO-->
                <td><a href="#">Outstanding invoices</a></td> <!--TODO-->
                <td><a href="#">Runs per technician</a></td> <!--TODO-->
			</tr>
			<tr>
                <td><a href="reportToRepeat.php">Samples to repeat</a></td>
				<td><a href="#">Cost per sample per run</a></td> <!--TODO-->
                <td><a href="#">Users overview</a></td> <!--TODO-->
			</tr>
			<tr>
                <td><a href="reportQc.php">QC parameters</a></td>
                <td></td>
                <td></td>
			</tr>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan="3"><a class='back' href='home.php'>Back to home page</a></td>
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