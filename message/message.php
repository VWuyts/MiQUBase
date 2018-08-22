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
createHead(true, 'MiQUBase message', ['message'], null);
createHeader('vwuyts', false);
?>
		<p class='message'>For 4 samples of run 2017_002</p>
		<p class='message'>a result sent date has been added in MiQUBase.</p>
		<p><a href='../php/home.php'>Back to home page</a></p>
<?php
createFooter(true);
?>