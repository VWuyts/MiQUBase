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
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase species', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Define input array
		$inputArr = [
			'speciesname'	=> null,
			'organismid'	=> null,
		];
		// Define error variables and set to empty value
		$nameErr = $foreignkeyidErr = "";
		
		// Create connection to database
		$dbconn = false;
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			if (isset($_POST['addSpecies']))
			{
				// Check user input
				if (isset($_POST['name']) && !empty(cleanInputText($_POST['name'])))
				{
					$inputArr['speciesname'] = cleanInputText($_POST['name']);
					if (strlen($inputArr['speciesname']) > 50)
					{
						$inputArr['speciesname'] = null;
						$nameErr = "Maximum 50 characters";
					}
					else
					{
						// Check if speciesName does not exists in database
						$query = "SELECT speciesid
								FROM species
								WHERE upper(speciesname) = upper($1)";
						$result = pg_query_params($dbconn, $query, [$inputArr['speciesname']]);
						if (pg_num_rows($result) > 0)
						{
							$inputArr['speciesname'] = null;
							$nameErr = "already in db";
						}
					}
				}
				else
				{
					$inputArr['speciesname'] = null;
					$nameErr = "Required";
				} // end check name
				if (isset($_POST['foreignkeyid']) && !empty(cleanInputInt($_POST['foreignkeyid'])))
				{
					$inputArr['organismid'] = cleanInputInt($_POST['foreignkeyid']);
					// Check if foreignkeyid exists in database
					$query = "SELECT organismname
							FROM organism
							WHERE organismid = $1";
					$result = pg_query_params($dbconn, $query, [$inputArr['organismid']]);
					if (pg_num_rows($result) < 1)
					{
						$inputArr['organismid'] = null;
						$foreignkeyidErr = "invalid";
					}
				}
				else
				{
					$inputArr['organismid'] = null;
					$foreignkeyidErr = "required";
				} // end check foreignkeyid
				// check if input is ok
				if (!empty($inputArr['speciesname']) && !empty($inputArr['organismid']))
				{
					unset($_POST['addSpecies']);
					unset($_POST['name']);
					unset($_POST['foreignkeyid']);
					// Insert into database
					$result = pg_insert($dbconn, 'species', $inputArr);
					if (!$result)
					{
						pg_close($dbconn);
						trigger_error('022@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						pg_close($dbconn);
						// Create log message
						$activitylogger->info('adding species to db succeeded', ['user'=>$_SESSION['user'], 'species'=>$inputArr['speciesname'], 'organismid'=>$inputArr['organismid']]);
						// Redirect user to message page
						createMessagePage(["Species '". $inputArr['speciesname'] ."'", "has been added to MiQUBase."], $_SESSION['user'],
							"../php/administrativeTasks.php", "Back to tasks overview page");
						die();
					}
				}
			}
			
			// Get organism data
			// Create array to collect query results
			$organismArr = [
				'id'	=> array(),
				'name'	=> array(),
			];
			$query = "SELECT DISTINCT organismid, organismname
					FROM organism
					ORDER BY organismname ASC";
			$result = pg_query($dbconn, $query);
			if (!$result)
			{
				pg_close($dbconn);
				trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			else
			{
				while($arr = pg_fetch_array($result))
				{
					$organismArr['id'][] = $arr['organismid'];
					$organismArr['name'][] = $arr['organismname'];
				}
			}
		}
?>
		<!-- show form -->
		<h1>Add a new species to MiQUBase</h1>
		<form method="post" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>">
			<p>
				<label for="name">Species name:</label>
				<input id="name" type="text" name="name" title="e.g. Salmonella" maxlength="50" autofocus required />
				<span class="error">* <?php echo($nameErr); ?></span>
			</p>
			<p>
				<label for="foreignkeyid">Organism:</label>
				<?php
					for ($i = 0; $i < count($organismArr['id']); $i++)
					{
						if ($i == 0)
						{
							echo("<input id='foreignkeyid' type='radio' name='foreignkeyid' value='". $organismArr['id'][$i] ."' />\n");
							echo("\t\t\t\t<span id='initials'>". $organismArr['name'][$i] ."</span>\n");
							echo("\t\t\t\t<span class='error'> * ". $foreignkeyidErr ."</span>\n\t\t\t</p>\n");
						}
						else
						{
							echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='radio' name='foreignkeyid' value=". $organismArr['id'][$i] ." />". $organismArr['name'][$i] ."\n\t\t\t</p>\n");
						}
					}
				?>
			
			<div class='buttonBox'>
				<input class="button right" type="submit" name="addSpecies" value="Add to MiQUBase" />
				<a class='right' href='administrativeTasks.php'>Cancel</a>
				<p class='spacer'></p>
			</div>
		</form>
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