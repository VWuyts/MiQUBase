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
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase project', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Define input array
		$inputArr = [
			'projectnumber'	=> null,
			'divisionid'	=> null,
			'isactive'		=> true,
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
			if (isset($_POST['addProject']))
			{
				// Check user input
				if (isset($_POST['name']) && !empty(cleanInputText($_POST['name'])))
				{
					$inputArr['projectnumber'] = cleanInputText($_POST['name']);
					if (strlen($inputArr['projectnumber']) > 70)
					{
						$inputArr['projectnumber'] = null;
						$nameErr = "Maximum 70 characters";
					}
					else
					{
						// Check if projectnumber does not exists in database
						$query = "SELECT projectid
								FROM project
								WHERE upper(projectnumber) = upper($1)";
						$result = pg_query_params($dbconn, $query, [$inputArr['projectnumber']]);
						if (pg_num_rows($result) > 0)
						{
							$inputArr['projectnumber'] = null;
							$nameErr = "already in db";
						}
					}
				}
				else
				{
					$inputArr['projectnumber'] = null;
					$nameErr = "Required";
				} // end check name
				if (isset($_POST['foreignkeyid']) && !empty(cleanInputInt($_POST['foreignkeyid'])))
				{
					$inputArr['divisionid'] = cleanInputInt($_POST['foreignkeyid']);
					// Check if foreignkeyid exists in database
					$query = "SELECT divisionname
							FROM division
							WHERE divisionid = $1";
					$result = pg_query_params($dbconn, $query, [$inputArr['divisionid']]);
					if (pg_num_rows($result) < 1)
					{
						$inputArr['divisionid'] = null;
						$foreignkeyidErr = "invalid";
					}
				}
				else
				{
					$inputArr['divisionid'] = null;
					$foreignkeyidErr = "required";
				} // end check foreignkeyid
				// check if input is ok
				if (!empty($inputArr['projectnumber']) && !empty($inputArr['divisionid']))
				{
					unset($_POST['addProject']);
					unset($_POST['name']);
					unset($_POST['foreignkeyid']);
					// Insert into database
					$result = pg_insert($dbconn, 'project', $inputArr);
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
						$activitylogger->info('adding project to db succeeded', ['user'=>$_SESSION['user'], 'project'=>$inputArr['projectnumber'], 'divisionid'=>$inputArr['divisionid']]);
						// Redirect user to message page
						createMessagePage(["Project '". $inputArr['projectnumber'] ."'", "has been added to MiQUBase."], $_SESSION['user'],
							"../php/administrativeTasks.php", "Back to tasks overview page");
						die();
					}
				}
			}
			
			// Get division data
			// Create array to collect query results
			$divisionArr = [
				'id'		=> null,
				'name'	=> null,
			];
			$query = "SELECT DISTINCT divisionid, divisionname
					FROM division
					WHERE isactive = $1
					ORDER BY divisionname ASC";
			$result = pg_query_params($dbconn, $query, [true]);
			if (!$result)
			{
				pg_close($dbconn);
				trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			else
			{
				while($arr = pg_fetch_array($result))
				{
					$divisionArr['id'][] = $arr['divisionid'];
					$divisionArr['name'][] = $arr['divisionname'];
				}
			}
		}
?>
		<!-- show form -->
		<h1>Add a new project to MiQUBase</h1>
		<form method="post" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>">
			<p>
				<label for="name">Project number:</label>
				<input id="name" type="text" name="name" title="e.g. PBB-FOD-4044.0100.0-BIOTECHlab" maxlength="70" autofocus required />
				<span class="error">* <?php echo($nameErr); ?></span>
			</p>
			<p>
				<label for="foreignkeyid">Division:</label>
				<?php
					for ($i = 0; $i < count($divisionArr['id']); $i++)
					{
						if ($i == 0)
						{
							echo("<input id='foreignkeyid' type='radio' name='foreignkeyid' value='". $divisionArr['id'][$i] ."' />\n");
							echo("\t\t\t\t<span id='initials'>". $divisionArr['name'][$i] ."</span>\n");
							echo("\t\t\t\t<span class='error'> * ". $foreignkeyidErr ."</span>\n\t\t\t</p>\n");
						}
						else
						{
							echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='radio' name='foreignkeyid' value=". $divisionArr['id'][$i] ." />". $divisionArr['name'][$i] ."\n\t\t\t</p>\n");
						}
					}
				?>
			
			<div class='buttonBox'>
				<input class="button right" type="submit" name="addProject" value="Add to MiQUBase" />
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