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
		createHead(true, 'MiQUBase libraryPrepKit', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Define input array
		$inputArr = [
			'libraryprepkitname'	=> null,
			'isactive'				=> true,
		];
		// Define error variable and set to empty value
		$nameErr = "";
		
		// Create connection to database
		$dbconn = false;
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			if (isset($_POST['addLibKit']))
			{
				// Check user input
				if (isset($_POST['name']) && !empty(cleanInputText($_POST['name'])))
				{
					$inputArr['libraryprepkitname'] = cleanInputText($_POST['name']);
					if (strlen($inputArr['libraryprepkitname']) > 30)
					{
						$inputArr['libraryprepkitname'] = null;
						$nameErr = "Maximum 30 characters";
					}
					else
					{
						// Check if libraryPrepKitName does not exists in database
						$query = "SELECT libraryprepkitid
								FROM libraryprepkit
								WHERE upper(libraryprepkitname) = upper($1)";
						$result = pg_query_params($dbconn, $query, [$inputArr['libraryprepkitname']]);
						if (pg_num_rows($result) > 0)
						{
							$inputArr['libraryprepkitname'] = null;
							$nameErr = "already in db";
						}
					}
				}
				else
				{
					$inputArr['libraryprepkitname'] = null;
					$nameErr = "Required";
				}
				// check if input is ok
				if (!empty($inputArr['libraryprepkitname']))
				{
					unset($_POST['addLibKit']);
					unset($_POST['name']);
					// Insert into database
					$result = pg_insert($dbconn, 'libraryprepkit', $inputArr);
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
						$activitylogger->info('adding libraryprepkit to db succeeded', ['user'=>$_SESSION['user'], 'libraryprepkit'=>$inputArr['libraryprepkitname']]);
						// Redirect user to message page
						createMessagePage(["LibraryPrepKit '". $inputArr['libraryprepkitname'] ."'", "has been added to MiQUBase."], $_SESSION['user'],
							"../php/administrativeTasks.php", "Back to tasks overview page");
						die();
					}
				}
			}
		}
?>
		<!-- show form -->
		<h1>Add a new libraryPrepKit to MiQUBase</h1>
		<form method="post" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>">
			<p>
				<label for="name">LibraryPrepKit name:</label>
				<input id="name" type="text" name="name" title="e.g. Nextera XT WGS" maxlength="30" autofocus required />
				<span class="error">* <?php echo($nameErr); ?></span>
			</p>
			
			<div class='buttonBox'>
				<input class="button right" type="submit" name="addLibKit" value="Add to MiQUBase" />
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