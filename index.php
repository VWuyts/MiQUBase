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
 * along with MiQUBase. If not, see <http://www.gnu.org/licenses/>.+
 */
	require 'php/functions.php';
	createHead(false, 'MiQUBase login', ['index'], null);
	createHeader(false, false);
?>
		<form method="post" action="php/login.php">
			<h1>Login</h1>
			<p> 
				<label for="username">Username:</label>
				<input id="username" type="text" name="username" maxlength="30" required autofocus>
			</p>
			<p>
				<label for="pw">Password:</label>
				<input id="pw" type="password" name="password" required>
			</p>
			<div class="buttonbox">
				<input class="button" type="submit" value="Submit" />
				<p class="spacer"></p>
			</div>
		</form>
<?php
	createFooter(false);
?>	
