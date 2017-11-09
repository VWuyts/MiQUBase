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
	// Redirect user to login form if required session variable is not set.
	if (!isset($_SESSION['role']))
	{
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
	else
	{
		// Redirect user to home page corresponding to role.
		switch($_SESSION['role'])
		{
			case 'creator':
				header("Location: home_admin.php");
				break;
			case 'administrator':
				header("Location: home_admin.php");
				break;
			case 'executor':
				header("Location: home_exec.php");
				break;
			case 'readonly':
				header("Location: home_readonly.php");
				break;
			default:
				header("Location: ../index.php");
				break;
		}
	}
?>