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
	// Check user role
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase add samples', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Define input array
		$inputArr = [
			'runId'			=> null,
			'runNumber'		=> null,
			'startDate'		=> null,
			'requestForm'	=> null,
			'savIndex'		=> null,
			'savSummary'	=> null,
		];
		// Define error variables and set to empty value
		$runIdErr = $requestFormErr = $savSummaryErr = $savIndexErr = "";
		// Create connection to database
		$dbconn = false;
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			if (isset($_POST['continue']))
			{
				// Check user inputs
				if (!isset($_POST['runId']))
				{
					$inputArr['runId'] = null;
					$runIdErr = "Required";
				}
				else
				{
					$query = "SELECT runnumber, startdate
							FROM run
							WHERE runId = $1";
					$result = pg_query_params($dbconn, $query, [cleanInputText($_POST['runId'])]);
					if (!$result)
					{
						pg_close($dbconn);
						trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					elseif (pg_num_rows($result) == 0)
					{
						$inputArr['runId'] = null;
						$runIdErr = "Not valid";
					}
					else
					{
						if (!($inputArr['runNumber'] = pg_fetch_result($result, 'runnumber'))
							|| !($inputArr['startDate'] = pg_fetch_result($result, 'startdate')))
						{
							pg_close($dbconn);
							$inputArr['runNumber'] = null;
							trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							$inputArr['runId'] = cleanInputText($_POST['runId']);
							$runIdErr = "";
						}
					}
				} // end check input runId
				if ($_FILES['requestForm']['size'] == 0
						|| $_FILES['requestForm']['size'] > 900000)
				{
					$inputArr['requestForm'] = null;
					$requestFormErr = "required";
				}
				else
				{
					$targetFile = $_SESSION['upload'] . '/' . $_SESSION['user'] . "_" . basename($_FILES['requestForm']['name']);
					$fileType = pathinfo($targetFile,PATHINFO_EXTENSION);
					if ($fileType != 'xlsx')
					{
						$inputArr['requestForm'] = null;
						$requestFormErr = "xlsx required";
					}
					else
					{
						$inputArr['requestForm'] = $targetFile;
						$requestFormErr = "";
						move_uploaded_file($_FILES['requestForm']['tmp_name'], $targetFile);
					}
				} // end check input requestForm
				if ($_FILES['savIndex']['size'] == 0
						|| $_FILES['savIndex']['size'] > 900000)
				{
					$inputArr['savIndex'] = null;
					$savIndexErr = "required";
				}
				else
				{
					$targetFile = $_SESSION['upload'] . '/' . $_SESSION['user'] . "_" . basename($_FILES['savIndex']['name']);
					$fileType = pathinfo($targetFile,PATHINFO_EXTENSION);
					if ($fileType != 'xlsx')
					{
						$inputArr['savIndex'] = null;
						$savIndexErr = "xlsx required";
					}
					else
					{
						$inputArr['savIndex'] = $targetFile;
						$savIndexErr = "";
						move_uploaded_file($_FILES['savIndex']['tmp_name'], $targetFile);
					}
				} // end check input savIndex
				if ($_FILES['savSummary']['size'] == 0
						|| $_FILES['savSummary']['size'] > 900000)
				{
					$inputArr['savSummary'] = null;
					$savSummaryErr = "required";
				}
				else
				{
					$targetFile = $_SESSION['upload'] . '/' . $_SESSION['user'] . "_" . basename($_FILES['savSummary']['name']);
					$fileType = pathinfo($targetFile,PATHINFO_EXTENSION);
					if ($fileType != 'xlsx')
					{
						$inputArr['savSummary'] = null;
						$savSummaryErr = "xlsx required";
					}
					else
					{
						$inputArr['savSummary'] = $targetFile;
						$savSummaryErr = "";
						move_uploaded_file($_FILES['savSummary']['tmp_name'], $targetFile);
					}
				} // end check input savSummary
				// check if all inputs are ok
				if (!empty($inputArr['runId'])
					&& !empty($inputArr['runNumber'])
					&& !empty($inputArr['requestForm'])
					&& !empty($inputArr['savIndex'])
					&& !empty($inputArr['savSummary']))
				{
					$_SESSION['inputArr'] = $inputArr;
					unset($_POST['continue']);
					unset($_POST['runId']);
					pg_close($dbconn);
					header("Location: addSampleOverview.php");
					die();
				}
			}
		}
?>
		<h1>Add samples to a run in MiQUBase</h1>
		<form method="post" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>" enctype="multipart/form-data"> <!--enctype="multipart/form-data" is needed for php file upload-->
<?php
		// Run number
		$query = "SELECT run.runid, run.runnumber
					FROM run
						LEFT JOIN lane
						ON run.runid = lane.runid
					WHERE lane.laneid IS NULL
					ORDER BY run.runnumber ASC;";
		$result = pg_query($dbconn, $query);
		if (!$result)
		{
			pg_close($dbconn);
			trigger_error('009@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			if (pg_num_rows($result) > 0)
			{
				echo("\t\t\t<p>\n\t\t\t\t<label for='runId'>Run number:</label>\n");
				echo("\t\t\t\t<select id='runId' name='runId' size='1' autofocus>\n");
				while($arr = pg_fetch_array($result))
				{
					if (isset($_POST['runId']) && ($_POST['runId'] == $arr['runid']))
					{
						echo("\t\t\t\t\t<option value=".$arr['runid']." selected>". $arr['runnumber'] ."</option>\n");
					}
					elseif (isset($_SESSION['inputArr']['runId']) && ($_SESSION['inputArr']['runId'] == $arr['runid']))
					{
						echo("\t\t\t\t\t<option value=".$arr['runid']." selected>". $arr['runnumber'] ."</option>\n");
					}
					else
					{
						echo("\t\t\t\t\t<option value=".$arr['runid'].">". $arr['runnumber'] ."</option>\n");
					}
				}
				echo("\t\t\t\t</select>\n\t\t\t\t<span class='error'> * ". $runIdErr ."</span>\n\t\t\t</p>\n");
			}
			else
			{
				pg_close($dbconn);
				trigger_error('010@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_NOTICE);
				die();
			}
		}
?>
			<p>
				<label for="requestForm">Request FORM:</label>
				<input type="hidden" name="MAX_FILE_SIZE" value="900000" /> <!--MAX_FILE_SIZE (in bytes) to ensure that file to be uploaded is not too big-->
				<input id="requestForm" type="file" name="requestForm" title="e.g.NGS-request_FORM.xlsx" required /> <!--requestForm is the name of the file in the global $_FILES -->
				<span class="error">* <?php echo($requestFormErr); ?></span>
			</p>
			<p>
				<label for="savIndex">SAV index tab:</label>
				<input type="hidden" name="MAX_FILE_SIZE" value="900000" /> <!--MAX_FILE_SIZE (in bytes) to ensure that file to be uploaded is not too big-->
				<input id="savIndex" type="file" name="savIndex" title="e.g.savIndex.xlsx" required /> <!--savIndex is the name of the file in the global $_FILES -->
				<span class="error">* <?php echo($savIndexErr); ?></span>
			</p>
			<p>
				<label for="savSummary">SAV summary tab:</label>
				<input type="hidden" name="MAX_FILE_SIZE" value="900000" /> <!--MAX_FILE_SIZE (in bytes) to ensure that file to be uploaded is not too big-->
				<input id="savSummary" type="file" name="savSummary" title="e.g.savSummary.xlsx" required /> <!--savSummary is the name of the file in the global $_FILES -->
				<span class="error">* <?php echo($savSummaryErr); ?></span>
			</p>
			<div class="buttonBox">
				<a class="buttonMarginRight" href="home.php">Cancel</a>
				<a class="buttonMarginRight" href="addSampleClear.php">Clear</a>
				<input class="button" type="submit" name="continue" value="Continue" />
			</div>
		</form>
<?php
		createFooter(true);
	}
	else
	{
		// Session variable isn't registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>