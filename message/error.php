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
require '../php/functions.php';
$script = "			window.setTimeout(function(){window.location = '../php/home.php';},10000);";
createHead(true, 'MiQUBase error', ['message'], $script);
createHeader(null, false);
?>
		<p class='message'>Login to MiQUBase failed.</p>
		<p class='message'>Please check your username and password.</p>
		<p class='message'>You will be redirected to the home page.</p>
		<p><a href='../php/home.php'>Back to home page</a></p>
<?php
createFooter(true);
?>
