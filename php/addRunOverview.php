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
	// Check required session variables
	if (isset($_SESSION['inputArr']) && isset($_SESSION['role'])
		&& (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase run overview', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			if (!unlink($_SESSION['inputArr']['runParXml']))
			{
				$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
			}
			unset($_SESSION['inputArr']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Allow user error handling
			libxml_use_internal_errors(true);
			// Parse runParameters.xml
			if (($xml = simplexml_load_file($_SESSION['inputArr']['runParXml'])) === false)
			{
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				$errMessages = array();
				foreach(libxml_get_errors() as $error)
				{
					array_push($errMessages, $error->message);
				}
				libxml_clear_errors();
				$errMessages = implode('@', $errMessages);
				trigger_error('007@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0@'.$errMessages, E_USER_ERROR);
				die();
			}
			else
			{
				// Load required fields from runParameters.xml
				$_SESSION['inputArr']['runId'] = (string)$xml->FlowcellRFIDTag->SerialNumber;
				$_SESSION['inputArr']['instrumentSn'] = (string)$xml->ScannerID;
				$startDateStr = (string)$xml->RunStartDate;
				// Put date in correct format
				$_SESSION['inputArr']['startDate'] = "20" . substr($startDateStr, 0, 2) . "-" . substr($startDateStr, 2, 2) . "-" . substr($startDateStr, 4, 2);
				// Set number of cycles in correct format
				$cycles = $cycles1 = $cycles2 = 0;
				foreach($xml->Reads->children() as $RunInfoRead)
				{
					// Take only non-index reads
					if ($RunInfoRead['IsIndexedRead'] == "N")
					{
						if ($RunInfoRead['Number'] == 1)
						{
							$cycles1 = (string)$RunInfoRead['NumCycles'];
						}
						else
						{
							$cycles2 = (string)$RunInfoRead['NumCycles'];
						}
					}
				}
				if ((int)$cycles1 === (int)$cycles2)
				{
					$cycles = "2X " . $cycles1;
				}
				elseif ((int)$cycles2 === 0)
				{
					$cycles = $cycles1;
				}
				else
				{
					$cycles = $cycles1 . " - " . $cycles2;
				}
				$_SESSION['inputArr']['numCycles'] = $cycles;
				$_SESSION['inputArr']['seqCartidge'] = (string)$xml->ReagentKitVersion;
			}
			// Get libraryPrepKit name
			$query = "SELECT libraryprepkitname
						FROM libraryprepkit
						WHERE libraryprepkitid = $1";
			$result = pg_query_params($dbconn, $query, [$_SESSION['inputArr']['libPrep']]);
			if (!$result)
			{
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (!($libraryprepkitname = pg_fetch_result($result, 'libraryprepkitname')))
			{
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			// Get indexKit name
			$query = "SELECT indexkitname
						FROM indexkit
						WHERE indexkitid = $1";
			$result = pg_query_params($dbconn, $query, [$_SESSION['inputArr']['indexKit']]);
			if (!$result)
			{
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			elseif (!($indexkitname = pg_fetch_result($result, 'indexkitname')))
			{
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['runParXml']))
				{
					$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
				die();
			}
			// Get operators initials
			// Create array to collect query results
			$initialsArr = array();
			$query = "SELECT initials
						FROM employee
						WHERE employeeID = $1";
			for ($i = 0; $i < count($_SESSION['inputArr']['operator']); $i++)
			{
				$result = pg_query_params($dbconn, $query, [$_SESSION['inputArr']['operator'][$i]]);
				if (!$result)
				{
					pg_close($dbconn);
					if (!unlink($_SESSION['inputArr']['runParXml']))
					{
						$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
					}
					unset($_SESSION['inputArr']);
					trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				else
				{
					if (!($name = pg_fetch_result($result, 'initials')))
					{
						pg_close($dbconn);
						if (!unlink($_SESSION['inputArr']['runParXml']))
						{
							$errorlogger->error('deleting uploaded file failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
						}
						unset($_SESSION['inputArr']);
						trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$initialsArr[] = $name;
					}
				}
			}
		}
?>
		<h1>Overview of run to add to MiQUBase</h1>
		<table>
			<tr>
				<td class="attribute">Run number:</td>
				<td><?php echo $_SESSION['inputArr']['runNumber']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Run ID:</td>
				<td><?php echo $_SESSION['inputArr']['runId']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Instrument SN:</td>
				<td><?php echo $_SESSION['inputArr']['instrumentSn']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Start date:</td>
				<td><?php echo $_SESSION['inputArr']['startDate']; ?></td>
			</tr>
			<tr>
				<td class="attribute">LibraryPrep kit:</td>
				<?php echo "<td>". $libraryprepkitname ."</td>"; ?>
			</tr>
			<tr>
				<td class="attribute">Index kit:</td>
				<?php echo "<td>". $indexkitname ."</td>"; ?>
			</tr>
			<tr>
				<td class="attribute">Library concentration (nM):</td>
				<td><?php echo $_SESSION['inputArr']['libConc']; ?></td>
			</tr>
			<tr>
				<td class="attribute" >Loading concentration (pM):</td>
				<td><?php echo $_SESSION['inputArr']['loadConc']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Kapa quantification:</td>
				<td><?php echo $_SESSION['inputArr']['kapa']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Number of cycles:</td>
				<td><?php echo $_SESSION['inputArr']['numCycles']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Reagent kit:</td>
				<td><?php echo $_SESSION['inputArr']['seqCartidge']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Operators:</td>
				<td>
				<?php
					for ($i = 0; $i < count($initialsArr); $i++)
					{
						echo $initialsArr[$i] . " ";
					}
				?>
				</td>
			</tr>
			<tr>
				<td class="attribute">Remarks:</td>
				<td><?php echo $_SESSION['inputArr']['remark']; ?></td>
			</tr>
		</table>
		<form method="post" action="addRunToDb.php">
			<div class="buttonBox">
				<a class="buttonMarginRight" href="home.php">Cancel</a>
				<a class="buttonMarginRight" href="addrun.php">Previous</a>
				<input class="button" type="submit" name="addrun" value="Add run" />
			</div>
		</form>
<?php
		createFooter(true);
	}
	else
	{
		// Session variables are not registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>