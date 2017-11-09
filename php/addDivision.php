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
		createHead(true, 'MiQUBase division', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Define input array
		$inputArr = [
			'divisionname'	=> null,
			'directorate'	=> null,
			'isactive'		=> true,
		];
		// Define error variables and set to empty value
		$nameErr = $directorateErr = "";
		
		// Create connection to database
		$dbconn = false;
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			if (isset($_POST['addDivision']))
			{
				// Check user input
				if (isset($_POST['name']) && !empty(cleanInputText($_POST['name'])))
				{
					$inputArr['divisionname'] = cleanInputText($_POST['name']);
					if (strlen($inputArr['divisionname']) > 50)
					{
						$inputArr['divisionname'] = null;
						$nameErr = "Maximum 50 characters";
					}
					else
					{
						// Check if divisionname does not exists in database
						$query = "SELECT divisionid
								FROM division
								WHERE upper(divisionname) = upper($1)";
						$result = pg_query_params($dbconn, $query, [$inputArr['divisionname']]);
						if (pg_num_rows($result) > 0)
						{
							$inputArr['divisionname'] = null;
							$nameErr = "already in db";
						}
					}
				}
				else
				{
					$inputArr['divisionname'] = null;
					$nameErr = "Required";
				} // end check name
				if ((isset($_POST['directorate']) && !empty(cleanInputText($_POST['directorate'])))
					|| (isset($_POST['new']) && !empty(cleanInputText($_POST['newdirectorate']))))
				{
					if (!empty(cleanInputText($_POST['newdirectorate'])))
					{
						$inputArr['directorate'] = cleanInputText($_POST['newdirectorate']);
						if (strlen($inputArr['directorate']) > 70)
						{
							$inputArr['directorate'] = null;
							$directorateErr = "Maximum 70 characters";
						}
					}
					else
					{
						$inputArr['directorate'] = cleanInputText($_POST['directorate']);
					}
				}
				else
				{
					$inputArr['directorate'] = null;
					$directorateErr = "required to select one";
				} // end check directorate
				// check if input is ok
				if (!empty($inputArr['divisionname']) && !empty($inputArr['directorate']))
				{
					unset($_POST['addDivision']);
					unset($_POST['name']);
					if (isset($_POST['directorate'])) unset($_POST['directorate']);
					if (isset($_POST['newdirectorate'])) unset($_POST['newdirectorate']);
					// Insert into database
					$result = pg_insert($dbconn, 'division', $inputArr);
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
						$activitylogger->info('adding division to db succeeded', ['user'=>$_SESSION['user'], 'division'=>$inputArr['divisionname'], 'directorate'=>$inputArr['directorate']]);
						// Redirect user to message page
						createMessagePage(["Division '". $inputArr['divisionname'] ."'", "of directorate '". $inputArr['directorate'] ."'",
							"has been added to MiQUBase."], $_SESSION['user'], "../php/administrativeTasks.php", "Back to tasks overview page");
						die();
					}
				}
			}
			
			// Get data on current directorates in MiQUBase
			// Create array to collect query results
			$directorateArr = array();
			$query = "SELECT DISTINCT directorate
						FROM division
						WHERE isactive = $1
						ORDER BY directorate ASC";
			$result = pg_query_params($dbconn, $query, [true]);
			if (!$result)
			{
				pg_close($dbconn);
				trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			else
			{
				for ($i = 0; $i < pg_num_rows($result); $i++)
				{
					$directorate = pg_fetch_result($result, $i, 'directorate');
					if ($directorate === false)
					{
						pg_close($dbconn);
						trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$directorateArr[] = $directorate;
					}
				}
			}
		}
?>
		<!-- show form -->
		<h1>Add a new division to MiQUBase</h1>
		<form method="post" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>">
			<p>
				<label for="name">Division name:</label>
				<input id="name" type="text" name="name" title="e.g. Platform Biotechnology and Molecular Biology" maxlength="50" autofocus required />
				<span class="error">* <?php echo($nameErr); ?></span>
			</p>
			<p>
				<label for="foreignkeyid">Directorate:</label>
				<?php
					for ($i = 0; $i < count($directorateArr); $i++)
					{
						if ($i == 0)
						{
							echo("<input id='foreignkeyid' type='radio' name='directorate' value='". $directorateArr[$i] ."' />\n");
							echo("\t\t\t\t<span id='initials'>". $directorateArr[$i] ."</span>\n");
							echo("\t\t\t\t<span class='error'> * ". $directorateErr ."</span>\n");
							echo("\t\t\t</p>\n");
						}
						else
						{
							echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='radio' name='directorate' value='". $directorateArr[$i] ."' />". $directorateArr[$i] ."\n\t\t\t</p>\n");
						}
					}
					echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='radio' name='new' value='new' />\n");
					echo("\t\t\t\t<input type='text' name='newdirectorate' title='e.g. Expertise, Services and Customer Relations' maxlength='70' />\n\t\t\t</p>");
				?>
			
			<div class='buttonBox'>
				<input class="button right" type="submit" name="addDivision" value="Add to MiQUBase" />
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