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
	// Check user role
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0 || 
		strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		createHead(true, 'MiQUBase add run', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Define input array
		$inputArr = [
			'runNumber'	=> null,
			'runParXml'	=> null,
			'operator'	=> null,
			'libPrep'	=> null,
			'indexKit'	=> null,
			'libConc'	=> null,
			'loadConc'	=> null,
			'kapa'		=> null,
			'remark'	=> null,
		];
		// Define error variables and set to empty value
		$runNumberErr = $runParXmlErr = $operatorErr = $libPrepErr = $indexKitErr = $libConcErr = $loadConcErr = $kapaErr = "";
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
				if (!isset($_POST['runNumber']) || (preg_match('/20[0-9]{2}_[0-9]{3}/', $_POST['runNumber']) === 0))
				{
					$inputArr['runNumber'] = null;
					$runNumberErr = "Required YYYY_xxx";
				}
				else
				{
					// Check if runNumber does not exists in database
					$query = "SELECT runID
							FROM run
							WHERE runNumber = $1";
					$result = pg_query_params($dbconn, $query, [$_POST['runNumber']]);
					if (empty(pg_fetch_result($result, 'runid')))
					{
						$inputArr['runNumber'] = $_POST['runNumber'];
						$runNumberErr = "";
					}
					else
					{
						$runNumberErr = $_POST['runNumber'] . " already in db";
						$inputArr['runNumber'] = null;
						$_POST['runNumber'] = null;
					}
				} // end check input runNumber
				if ($_FILES['runParXml']['size'] == 0
						|| strcmp('runParameters.xml', basename($_FILES['runParXml']['name'])) !== 0
						|| $_FILES['runParXml']['size'] > 10000)
				{
					$inputArr['runParXml'] = null;
					$runParXmlErr = "runParameters.xml";
				}
				else
				{
					$targetFile = $_SESSION['upload'] . '/' . $_SESSION['user'] . "_" . basename($_FILES['runParXml']['name']);
					$fileType = pathinfo($targetFile,PATHINFO_EXTENSION);
					if ($fileType != 'xml')
					{
						$inputArr['runParXml'] = null;
						$runParXmlErr = "runParameters.xml";
					}
					else
					{
						$inputArr['runParXml'] = $targetFile;
						$runParXmlErr = "";
						move_uploaded_file($_FILES['runParXml']['tmp_name'], $targetFile);
					}
				} // end check input runParXml
				if (!isset($_POST['operator']))
				{
					$inputArr['operator'] = null;
					$operatorErr = "Required";
				}
				else
				{
					$inputOk = true;
					$query = "SELECT initials
							FROM employee
							WHERE employeeID = $1";
					for ($i = 0; $i < count($_POST['operator']); $i++)
					{
						$_POST['operator'][$i] = cleanInputInt($_POST['operator'][$i]);
						$result = pg_query_params($dbconn, $query, [$_POST['operator'][$i]]);
						if (!$result)
						{
							pg_close($dbconn);
							trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						elseif (pg_num_rows($result) == 0)
						{
							$inputOk = false;
						}
					}
					if ($inputOk)
					{
						$inputArr['operator'] = $_POST['operator'];
						$operatorErr = "";
					}
					else
					{
						$inputArr['operator'] = null;
						$operatorErr = "Not valid";
					}
				} // end check input operator
				if (!isset($_POST['libPrep']))
				{
					$inputArr['libPrep'] = null;
					$libPrepErr = "Required";
				}
				else
				{
					$query = "SELECT libraryPrepKitName
							FROM libraryPrepKit
							WHERE libraryPrepKitID = $1";
					$result = pg_query_params($dbconn, $query, [cleanInputInt($_POST['libPrep'])]);
					if (!$result)
					{
						pg_close($dbconn);
						trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					elseif (pg_num_rows($result) == 0)
					{
						$inputArr['libPrep'] = null;
						$libPrepErr = "Not valid";
					}
					else
					{
						$inputArr['libPrep'] = cleanInputInt($_POST['libPrep']);
						$libPrepErr = "";
					}
				} // end check input libPrep
				if (!isset($_POST['indexKit']))
				{
					$inputArr['indexKit'] = null;
					$indexKitErr = "Required";
				}
				else
				{
					$query = "SELECT indexKitName
							FROM indexKit
							WHERE indexKitID = $1";
					$result = pg_query_params($dbconn, $query, [cleanInputInt($_POST['indexKit'])]);
					if (!$result)
					{
						pg_close($dbconn);
						trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					elseif (pg_num_rows($result) == 0)
					{
						$inputArr['indexKit'] = null;
						$indexKitErr = "Not valid";
					}
					else
					{
						$inputArr['indexKit'] = cleanInputInt($_POST['indexKit']);
						$indexKitErr = "";
					}
				} // end check input indexkit
				if (!isset($_POST['libConc']))
				{
					$inputArr['libConc'] = null;
					$libConcErr = "Required";
				}
				elseif (($_POST['libConc'] = cleanInputDecimal($_POST['libConc'])) < 1)
				{
					$inputArr['libConc'] = null;
					$libConcErr = "Concentration in nM";
				}
				else
				{
					$inputArr['libConc'] = $_POST['libConc'];
					$libConcErr = "";
				} // end check input libConc
				if (!isset($_POST['loadConc']))
				{
					$inputArr['loadConc'] = null;
					$loadConcErr = "Required";
				}
				elseif (($_POST['loadConc'] = cleanInputDecimal($_POST['loadConc'])) < 1)
				{
					$inputArr['loadConc'] = null;
					$loadConcErr = "Concentration in pM";
				}
				else
				{
					$inputArr['loadConc'] = $_POST['loadConc'];
					$loadConcErr = "";
				} // end check input loadConc
				if (!isset($_POST['kapa']))
				{
					$inputArr['kapa'] = null;
					$kapaErr = "";
				}
				elseif (($_POST['kapa'] = cleanInputDecimal($_POST['kapa'])) == "")
				{
					$inputArr['kapa'] = null;
					$kapaErr = "";
				}
				elseif (($_POST['kapa'] = cleanInputDecimal($_POST['kapa'])) < 0.01)
				{
					$inputArr['kapa'] = null;
					$kapaErr = "Concentration in nM";
				}
				else
				{
					$inputArr['kapa'] = $_POST['kapa'];
					$kapaErr = "";
				} // end check input kapa
				if (isset ($_POST['remark']) && !empty(cleanInputText($_POST['remark'])))
				{
					$inputArr['remark'] = $_SESSION['initials'] ." ". date('d/m/Y') .": " .cleanInputText($_POST['remark']);
				} // end check input remark
				// check if all inputs are ok
				if (!empty($inputArr['runNumber'])
					&& !empty($inputArr['runParXml'])
					&& !empty($inputArr['operator'])
					&& !empty($inputArr['libPrep'])
					&& !empty($inputArr['indexKit'])
					&& !empty($inputArr['libConc'])
					&& !empty($inputArr['loadConc']))
				{
					$_SESSION['inputArr'] = $inputArr;
					unset($_POST['continue']);
					unset($_POST['runNumber']);
					unset($_POST['operator']);
					unset($_POST['libPrep']);
					unset($_POST['indexKit']);
					unset($_POST['libConc']);
					unset($_POST['loadConc']);
					if (isset($_POST['kapa'])) unset($_POST['kapa']);
					if (isset($_POST['remark'])) unset($_POST['remark']);
					pg_close($dbconn);
					header("Location: addRunOverview.php");
					die();
				}
			}
			
			// Get data on operators, libraryPrepKits and indexKits
			// Create arrays to collect query results
			$operatorArr = [
				'id'		=> null,
				'initials'	=> null,
			];
			$libraryArr = [
				'id'	=> null,
				'name'	=> null,
			];
			$indexArr = [
				'id'	=> null,
				'name'	=> null,
			];
			// Operators
			$query = "SELECT employeeid, initials
						FROM employee
						WHERE isactive = $1 AND istechnician = $1
						ORDER BY initials ASC";
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
					$operatorArr['id'][] = $arr['employeeid'];
					$operatorArr['initials'][] = $arr['initials'];
				}
			}
			// LibraryPrep kit
			$query = "SELECT libraryprepkitid, libraryprepkitname
						FROM libraryprepkit
						WHERE isactive = $1
						ORDER BY libraryprepkitname ASC";
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
					$libraryArr['id'][] = $arr['libraryprepkitid'];
					$libraryArr['name'][] = $arr['libraryprepkitname'];
				}
			}
			// Index kit
			$query = "SELECT indexkitid, indexkitname
						FROM indexkit
						WHERE isactive = $1
						ORDER BY indexkitname ASC";
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
					$indexArr['id'][] = $arr['indexkitid'];
					$indexArr['name'][] = $arr['indexkitname'];
				}
			}
		}
?>
		<h1>Add a new run to MiQUBase</h1>
		<form method="post" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'])); ?>" enctype="multipart/form-data"> <!--enctype="multipart/form-data" is needed for php file upload-->
			<!-- Run number -->
			<p>
				<label for="runNumber">Run number:</label>
				<input id="runNumber" type="text" name="runNumber" placeholder="YYYY_xxx" pattern="20[0-9]{2}_[0-9]{3}" title="e.g. 2017_001" autofocus required value="<?php if (isset($_POST['runNumber'])){echo($_POST['runNumber']);} elseif (isset($_SESSION['inputArr']['runNumber'])){echo($_SESSION['inputArr']['runNumber']);} ?>" />
				<span class="error">* <?php echo($runNumberErr); ?></span>
			</p>
			<!-- xml file -->
			<p>
				<label for="runParXml">runParameters.xml:</label>
				<input type="hidden" name="MAX_FILE_SIZE" value="10000" /> <!--MAX_FILE_SIZE (in bytes) to ensure that file to be uploaded is not too big-->
				<input id="runParXml" type="file" name="runParXml" title="e.g.runParameters.xml" required /> <!--runParXml is the name of the file in the global $_FILES -->
				<span class="error">* <?php echo($runParXmlErr); ?></span>
			</p>
<?php
	// Operators
	echo("\t\t\t<!-- Operators -->\n");
	echo("\t\t\t<p>\n\t\t\t\t<label for='operator'>Operators:</label>\n");
	for ($i = 0; $i < count($operatorArr['id']); $i++)
	{
		if ($i == 0)
		{
			if (isset($_POST['operator']) && in_array($operatorArr['id'][$i], $_POST['operator']))
			{
				echo("\t\t\t\t<input id='operator' type='checkbox' name='operator[]' value=".$operatorArr['id'][$i]." checked /><span id='initials'>".$operatorArr['initials'][$i]."</span>\n");
			}
			elseif (isset($_SESSION['inputArr']['operator']) && in_array($operatorArr['id'][$i], $_SESSION['inputArr']['operator']))
			{
				echo("\t\t\t\t<input id='operator' type='checkbox' name='operator[]' value=".$operatorArr['id'][$i]." checked /><span id='initials'>".$operatorArr['initials'][$i]."</span>\n");
			}
			else
			{
				echo("\t\t\t\t<input id='operator' type='checkbox' name='operator[]' value=".$operatorArr['id'][$i]." /><span id='initials'>".$operatorArr['initials'][$i]."</span>\n");
			}
			echo("\t\t\t\t<span class='error'> * ". $operatorErr ."</span>\n\t\t\t</p>\n");
		}
		else
		{
			if (isset($_POST['operator']) && in_array($operatorArr['id'][$i], $_POST['operator']))
			{
				echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='checkbox' name='operator[]' value=".$operatorArr['id'][$i]." checked />".$operatorArr['initials'][$i]."\n\t\t\t</p>\n");
			}
			elseif (isset($_SESSION['inputArr']['operator']) && in_array($operatorArr['id'][$i], $_SESSION['inputArr']['operator']))
			{
				echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='checkbox' name='operator[]' value=".$operatorArr['id'][$i]." checked />".$operatorArr['initials'][$i]."\n\t\t\t</p>\n");
			}
			else
			{
				echo("\t\t\t<p>\n\t\t\t\t<input class='operators' type='checkbox' name='operator[]' value=".$operatorArr['id'][$i]." />".$operatorArr['initials'][$i]."\n\t\t\t</p>\n");
			}
		}
	}
	// LibraryPrep kit
	echo("\t\t\t<!-- LibraryPrep kit -->\n");
	echo("\t\t\t<p>\n\t\t\t\t<label for='libPrep'>LibraryPrep kit:</label>\n");
	echo("\t\t\t\t<select id='libPrep' name='libPrep' size='1'>\n");
	for ($i = 0; $i < count($libraryArr['id']); $i++)
	{
		if (isset($_POST['libPrep']) && ($_POST['libPrep'] == $libraryArr['id'][$i]))
		{
			echo("\t\t\t\t\t<option value=".$libraryArr['id'][$i]." selected>".$libraryArr['name'][$i]."</option>\n");
		}
		elseif (isset($_SESSION['inputArr']['libPrep']) && ($_SESSION['inputArr']['libPrep'] == $libraryArr['id'][$i]))
		{
			echo("\t\t\t\t\t<option value=".$libraryArr['id'][$i]." selected>".$libraryArr['name'][$i]."</option>\n");
		}
		elseif ($libraryArr['id'][$i] == 1)
		{
			echo("\t\t\t\t\t<option value=".$libraryArr['id'][$i]." selected>".$libraryArr['name'][$i]."</option>\n");
		}
		else
		{
			echo("\t\t\t\t\t<option value=".$libraryArr['id'][$i].">".$libraryArr['name'][$i]."</option>\n");
		}
	}
	echo("\t\t\t\t</select>\n\t\t\t\t<span class='error'> * ". $libPrepErr ."</span>\n\t\t\t</p>\n");
	// Index kit
	echo("\t\t\t<!-- Index kit -->\n");
	echo("\t\t\t<p>\n\t\t\t\t<label for='indexKit'>Index kit:</label>\n");
	echo("\t\t\t\t<select id='indexKit' name='indexKit' size='1'>\n");
	for ($i = 0; $i < count($indexArr['id']); $i++)
	{
		if (isset($_POST['indexKit']) && ($_POST['indexKit'] == $indexArr['id'][$i]))
		{
			echo("\t\t\t\t\t<option value=".$indexArr['id'][$i]." selected>".$indexArr['name'][$i]."</option>\n");
		}
		elseif (isset($_SESSION['inputArr']['indexKit']) && ($_SESSION['inputArr']['indexKit'] == $indexArr['id'][$i]))
		{
			echo("\t\t\t\t\t<option value=".$indexArr['id'][$i]." selected>".$indexArr['name'][$i]."</option>\n");
		}
		else
		{
			echo("\t\t\t\t\t<option value=".$indexArr['id'][$i].">".$indexArr['name'][$i]."</option>\n");
		}
	}
	echo("\t\t\t\t</select>\n\t\t\t\t<span class='error'> * ". $indexKitErr ."</span>\n\t\t\t</p>\n");
?>
			<!-- Concentration, kappa and remarks -->
			<p>
				<label for="libConc">Pooled library concentration (nM):</label>
				<input id="libConc" type="number" name="libConc" title="e.g. 1 or 2 or 4" min="0.01" step="0.01" required value="<?php if (isset($_POST['libConc'])){echo($_POST['libConc']);} elseif ( isset($_SESSION['inputArr']['libConc'])){echo($_SESSION['inputArr']['libConc']);} ?>" />
				<span class="error">* <?php echo($libConcErr);?></span>
			</p>
			<p>
				<label for="loadConc">Loading concentration (pM):</label>
				<input id="loadConc" type="number" name="loadConc" title="e.g. 10 or 12 or 15" min="0.01" step="0.01" required value="<?php if (isset($_POST['loadConc'])){echo($_POST['loadConc']);} elseif (isset($_SESSION['inputArr']['loadConc'])){echo($_SESSION['inputArr']['loadConc']);} ?>" />
				<span class="error">* <?php echo($loadConcErr);?></span>
			</p>
			<p>
				<label for="kapa">KAPA quantification (nM):</label>
				<input id="kapa" type="number" name="kapa" title="e.g. 2.3" min="0.01" step="0.01" value="<?php if (isset($_POST['kapa'])){echo($_POST['kapa']);} elseif (isset($_SESSION['inputArr']['kapa'])){echo($_SESSION['inputArr']['kapa']);} ?>" />
				<span class="error">&nbsp;<?php echo($kapaErr);?></span>
			</p>
			<p>
				<label for="remark">Remarks:</label>
				<textarea id="remark" name="remark"><?php if (isset($_POST['remark'])){echo(htmlspecialchars($_POST['remark']));} elseif (isset($_SESSION['inputArr']['remark'])) {$index = strpos($_SESSION['inputArr']['remark'], ':'); $_SESSION['inputArr']['remark'] = substr($_SESSION['inputArr']['remark'], $index+1); echo($_SESSION['inputArr']['remark']);} ?></textarea>
			</p>
			<!-- Buttons -->
			<div class="buttonBox">
				<a class="buttonMarginRight" href="home.php">Cancel</a>
				<a class="buttonMarginRight" href="addRunClear.php">Clear</a>
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