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
	// Check required session variables
	if (isset($_SESSION['role']) && (strcmp($_SESSION['role'], 'administrator') == 0
		|| strcmp($_SESSION['role'], 'readonly') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		$script = "\t\t\tvar checkbutns = document.getElementsByClassName('checkbutton');\n";
		$script .= "\t\t\tvar uncheckbutns = document.getElementsByClassName('uncheckbutton');\n";
		$script .= "\t\t\tfor (var butn in checkbutns){checkbutns[butn].onclick = checkAll;}\n";
		$script .= "\t\t\tfor (var butn in uncheckbutns){uncheckbutns[butn].onclick = uncheckAll;}\n";
		$script .= "\t\t\tfunction checkAll(){\n";
		$script .= "\t\t\t\tvar checks = document.getElementsByClassName(this.name);\n";
		$script .= "\t\t\t\tif (checks){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < checks.length; i++){\n";
		$script .= "\t\t\t\t\t\tchecks[i].checked = true;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n";
		$script .= "\t\t\tfunction uncheckAll(){\n";
		$script .= "\t\t\t\tvar checks = document.getElementsByClassName(this.name);\n";
		$script .= "\t\t\t\tif (checks){\n";
		$script .= "\t\t\t\t\tfor (var i = 0; i < checks.length; i++){\n";
		$script .= "\t\t\t\t\t\tchecks[i].checked = false;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}";
		createHead(true, 'MiQUBase query', ['query'], $script);
		createHeader($_SESSION['user'], true);
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Declare variables to collect all inputs for the query
			$join = "FROM run
						LEFT JOIN lane ON run.runid = lane.runid
						LEFT JOIN sample ON lane.laneid = sample.laneid
						LEFT JOIN invoice ON sample.invoiceid = invoice.invoiceid
						LEFT JOIN project ON sample.projectid = project.projectid
						LEFT JOIN division ON project.divisionid = division.divisionid
						LEFT JOIN species ON sample.speciesid = species.speciesid
						LEFT JOIN organism ON species.organismid = organism.organismid
						LEFT JOIN ngsread ON lane.laneid = ngsread.laneid
						LEFT JOIN summarytotal ON lane.laneid = summarytotal.laneid
						LEFT JOIN runemployee ON run.runid = runemployee.runID
						LEFT JOIN employee ON runemployee.employeeid = employee.employeeid
						LEFT JOIN libraryprepkit ON run.libraryprepkitid = libraryprepkit.libraryprepkitid
						LEFT JOIN indexkit ON run.indexkitid = indexkit.indexkitid";
			$fields = array(
				'division'			=> array('divisionID', 'divisionName', 'directorate', 'isActive'),
				'employee'			=> array('employeeID', 'firstName', 'lastName', 'initials', 'divisionID', 'username', 'isTechnician', 'isActive', 'lastLogin', 'loginAttempts'),
				'indexkit'			=> array('indexKitID', 'indexKitName', 'isActive'),
				'invoice'			=> array('invoiceID', 'runID', 'projectID', 'invoiceDate', 'amount', 'paymentDate'),
				'lane'				=> array('laneID', 'laneNumber', 'runID', 'tiles', 'totalReads', 'readsPF', 'readsIdentifiedPF', 'cv'),
				'libraryprepkit'	=> array('libraryPrepKitID', 'libraryPrepKitName', 'isActive'),
				'ngsread'			=> array('ngsReadID', 'laneID','readNumber', 'isIndexedRead', 'density', 'density_SD', 'clusterPF', 'clusterPF_SD', 'phasing',
												'prephasing', 'noReads', 'noReadsPF', 'q30', 'yield', 'cyclesErrRated', 'aligned', 'aligned_SD', 'errorRate',
												'errorRate_SD', 'errorRate35', 'errorRate35_SD', 'errorRate75', 'errorRate75_SD', 'errorRate100', 'errorRate100_SD',
												'intensityCycle1', 'intensityCycle1_SD'),
				'organism'			=> array('organismID', 'organismName'),
				'project'			=> array('projectID', 'projectNumber', 'divisionID', 'isActive'),
				'run'				=> array('runID', 'runNumber', 'instrumentSN', 'startDate', 'libraryPrepKitID', 'loadingConcentration', 'kapaQuantification', 'indexKitID',
												'numCycles', 'sequencingCartridge', 'organismID', 'totalCost', 'remark', 'pooledLibraryConcentration'),
				'runemployee'		=> array('runID', 'employeeID'),
				'sample'			=> array('sampleID', 'sampleName', 'speciesID', 'projectID', 'laneID', 'receptionDate', 'resultSentDate', 'sop', 'priority', 'toRepeat',
												'isRepeatOf', 'r_d', 'indexNumber', 'index1_I7', 'index2_I5', 'readsIdentifiedPF', 'remark'),
				'species'			=> array('speciesID', 'speciesName', 'organismID'),
				'summarytotal'		=> array('summaryTotalID', 'laneID', 'isNonIndexedTotal', 'yieldTotal', 'aligned', 'errorRate', 'intensityCycle1', 'q30'),
			);
			$where = null;
			
			// Check for selected tables of database
			if (isset($_POST['table']) && !empty($_POST['dbtable']))
			{				
				$_SESSION['dbtable'] = $_POST['dbtable'];
				unset($_POST['dbtable']);
				$_SESSION['fields'] = array();
			}
			// Check for selected fields of tables
			if (isset($_POST['fields']) && isset($_SESSION['dbtable']))
			{
				$keys = array();
				$_SESSION['fields'] = array();
				$_SESSION['fieldvalue'] = array();
				for ($i = 0; $i < count($_SESSION['dbtable']); $i++)
				{
					if (isset($_POST[$_SESSION['dbtable'][$i]]) && !empty($_POST[$_SESSION['dbtable'][$i]]))
					{
						array_push($_SESSION['fields'], $_POST[$_SESSION['dbtable'][$i]]);
						array_push($keys, $_SESSION['dbtable'][$i]);
						for ($j = 0; $j < count($_SESSION['fields'][$i]); $j++)
						{
							$arr = array();
							$valueQuery = "SELECT DISTINCT ". strtolower($_SESSION['fields'][$i][$j]) ." FROM ". $_SESSION['dbtable'][$i] ." ORDER BY 1 DESC;";
							$result = pg_query($dbconn, $valueQuery);
							if (!$result)
							{
								pg_close($dbconn);
								if (isset($_SESSION['distinct'])) unset($_SESSION['distinct']);
								if (isset($_SESSION['dbtable'])) unset($_SESSION['dbtable']);
								if (isset($_SESSION['fields'])) unset($_SESSION['fields']);
								if (isset($_SESSION['fieldvalue'])) unset($_SESSION['fieldvalue']);
								if (isset($_SESSION['order'])) unset($_SESSION['order']);
								if (isset($_SESSION['orderby'])) unset($_SESSION['orderby']);
								if (isset($_SESSION['query'])) unset($_SESSION['query']);
								if (isset($_SESSION['where'])) unset($_SESSION['where']);
								trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
								die();
							}
							elseif (pg_num_rows($result) == 0)
							{
								$arr = array('IS NULL', 'IS NOT NULL');
							}
							else
							{
								for ($k = 0; $k < pg_num_rows($result); $k++)
								{
									$val = htmlspecialchars(pg_fetch_row($result)[0]);
									if ($val === false)
									{
										pg_close($dbconn);
										if (isset($_SESSION['distinct'])) unset($_SESSION['distinct']);
										if (isset($_SESSION['dbtable'])) unset($_SESSION['dbtable']);
										if (isset($_SESSION['fields'])) unset($_SESSION['fields']);
										if (isset($_SESSION['fieldvalue'])) unset($_SESSION['fieldvalue']);
										if (isset($_SESSION['order'])) unset($_SESSION['order']);
										if (isset($_SESSION['orderby'])) unset($_SESSION['orderby']);
										if (isset($_SESSION['query'])) unset($_SESSION['query']);
										if (isset($_SESSION['where'])) unset($_SESSION['where']);
										trigger_error('021@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
										die();
									}
									if (strcmp($val, 't') == 0) $val = 'TRUE';
									elseif (strcmp($val, 'f') == 0) $val = 'FALSE';
									$arr[] = $val;
								}
								array_push($arr, 'IS NULL', 'IS NOT NULL');
							}
							$_SESSION['fieldvalue'][$i][$j] = $arr;
						}
					}
				}
				// Add table names as keys
				$_SESSION['fields'] = array_combine($keys, $_SESSION['fields']);
				// Set table names to lower case
				$_SESSION['fields'] = array_change_key_case($_SESSION['fields'], CASE_LOWER);
				unset($_POST['fields']);
			}
			// Check if rows are selected (SQL: WHERE)
			if (isset($_POST['where']))
			{
				$_SESSION['where'] = null;
				if (!empty($_POST['rows']))
				{
					$where = "WHERE";
					$andor = "AND";
					$counter = 0;
					for ($i = 0; $i < count($_SESSION['fields']); $i++)
					{
						for ($j = 0; $j < count($_SESSION['fields'][$_SESSION['dbtable'][$i]]); $j++)
						{
							$value = $_SESSION['dbtable'][$i].".".$_SESSION['fields'][$_SESSION['dbtable'][$i]][$j];
							if (array_search($value, $_POST['rows']) !== false)
							{
								if ($counter > 0)
								{
									$where .= " ". $andor;
								}
								$where .= " ". strtolower($value);
								$operator = " = ";
								$fieldvalue = "IS NULL";
								if (!empty($_POST["operator".$i."_".$j]))
								{
									switch ($_POST["operator".$i."_".$j])
									{
										case "eq":
											$operator = " = ";
											break;
										case "neq":
											$operator = " <> ";
											break;
										case "lt":
											$operator = " < ";
											break;
										case "gt":
											$operator = " > ";
											break;
										case "le":
											$operator = " <= ";
											break;
										case "ge":
											$operator =" >= ";
											break;
									}
								}
								if (!empty($_POST["value".$i."_".$j]))
								{
									$fieldvalue = $_POST["value".$i."_".$j];
								}
								if (!empty($_POST["andor".$i."_".$j]))
								{
									$andor =  $_POST["andor".$i."_".$j];
								}
								else
								{
									$andor = "AND";
								}
								if (strcmp($fieldvalue, "IS NULL") == 0 || strcmp($fieldvalue, "IS NOT NULL") == 0)
								{
									$where .= " ". $fieldvalue;
								}
								else
								{
									$where .= $operator;
									if (is_numeric($fieldvalue) || is_bool($fieldvalue))
									{
										$where .= $fieldvalue;
									}
									else
									{
										$where .= "'". $fieldvalue ."'";
									}
								}
								$counter++;
							}
						}
					}
					$_SESSION['where'] = $where;
				}
			}			
			// Check if distinct is checked
			if (!empty($_POST['distinct']))
			{
				$_SESSION['distinct'] = true;
			}
			else
			{
				$_SESSION['distinct'] = false;
			}
			// Check if order is checked
			if (!empty($_POST['order']))
			{
				$_SESSION['order'] = $_POST['ordering'];
				$_SESSION['orderby'] = $_POST['orderby'];
			}
			else
			{
				$_SESSION['order'] = false;
				$_SESSION['orderby'] = null;
			}
			
			// Create query
			$query = null;
			// SELECT
			$select = "SELECT";
			$from = "FROM";
			if ($_SESSION['distinct'])
			{
				$select .= " DISTINCT";
			}
			if (!empty($_SESSION['fields']))
			{
				$table = array_keys($_SESSION['fields']);
				 
				for ($i = 0; $i < count($_SESSION['fields']); $i++)
				{
					if ($i > 0 && $i < count($_SESSION['fields']))
					{
						$from .= " join";
						$select .= ",";
					}
					$from .= " ".$table[$i];
					for ($j = 0; $j < count($_SESSION['fields'][$table[$i]]); $j++)
					{
						if ($j > 0 && $j < count($_SESSION['fields'][$table[$i]])) $select .= ",";
						$select .= " ".$table[$i]. "." .strtolower($_SESSION['fields'][$table[$i]][$j]);
					}
				}
				// FROM
				$query = $select ."\n".$from;
				$_SESSION['query'] = $select;
				if (count($_SESSION['dbtable']) == 1)
				{
					$_SESSION['query'] .= " FROM ".$_SESSION['dbtable'][0];
				}
				else
				{
					$_SESSION['query'] .= " ".$join;
				}
				// WHERE
				if (!empty($_SESSION['where']))
				{
					$query .= "\n". $_SESSION['where'];
					$_SESSION['query'] .= " ". $_SESSION['where'];
				}
				// ORDER BY
				if (!empty($_SESSION['order']) && !empty($_SESSION['orderby']))
				{
					$query .= "\nORDER BY ". strtolower($_SESSION['orderby']);
					$query .= " " . $_SESSION['order'];
					$_SESSION['query'] .= " ORDER BY ". strtolower($_SESSION['orderby']);
					$_SESSION['query'] .= " " . $_SESSION['order'];
				}
				$query .= ";";
				$_SESSION['query'] .= ";";
			}
			else
			{
				$_SESSION['query'] = null;
				$query = null;
			}
						
			// Show query builder
			echo("\t\t<h1>Query the database</h1>\n");
			echo("\t\t<div class='buttonbox'>\n");
			echo("\t\t\t<a class='right' href='dbScheme.php' target='_blank'>Open database scheme</a>\n");
			echo("\t\t\t<a class='right' href='queryClear.php'>Clear inputs</a>\n");
			echo("\t\t\t<p class='spacer'></p>\n");
			echo("\t\t</div>\n");
			
			// Show database tables (SQL: FROM)
			echo("\t\t<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">\n");
			echo("\t\t\t<h3>Select table(s)</h3>\n");
			echo("\t\t\t<table>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='lane' ");
			if (isset($_SESSION['dbtable']) && array_search('lane', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>lane</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='ngsread' ");
			if (isset($_SESSION['dbtable']) && array_search('ngsread', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>ngsRead</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='run' ");
			if (isset($_SESSION['dbtable']) && array_search('run', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>run</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='runemployee' ");
			if (isset($_SESSION['dbtable']) && array_search('runemployee', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>runEmployee</td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='sample' ");
			if (isset($_SESSION['dbtable']) && array_search('sample', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>sample</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='summarytotal' ");
			if (isset($_SESSION['dbtable']) && array_search('summarytotal', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>summaryTotal</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='invoice' ");
			if (isset($_SESSION['dbtable']) && array_search('invoice', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>invoice</td><td class='check'></td><td class='dbitem'></td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='division' ");
			if (isset($_SESSION['dbtable']) && array_search('division', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>division</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='employee' ");
			if (isset($_SESSION['dbtable']) && array_search('employee', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>employee</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='indexkit' ");
			if (isset($_SESSION['dbtable']) && array_search('indexkit', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>indexKit</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='libraryprepkit' ");
			if (isset($_SESSION['dbtable']) && array_search('libraryprepkit', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>libraryPrepKit</td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td  class='check'><input type='checkbox' name='dbtable[]' value='organism' ");
			if (isset($_SESSION['dbtable']) && array_search('organism', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>organism</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='project' ");
			if (isset($_SESSION['dbtable']) && array_search('project', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>project</td>\n");
			echo("\t\t\t\t\t<td class='check'><input type='checkbox' name='dbtable[]' value='species' ");
			if (isset($_SESSION['dbtable']) && array_search('species', $_SESSION['dbtable']) !== false) echo("checked ");
			echo("/></td>\n\t\t\t\t\t<td class='dbitem'>species</td>\n");
			echo("\t\t\t\t\t<td></td>\n\t\t\t\t\t<td></td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='8'><input class='ok' type='submit' name='table' value='OK' /></td>\n\t\t\t\t</tr>\n");
			echo("\t\t\t</table>\n\t\t</form>\n");
			
			// Show fields of selected tables (SQL: SELECT)
			echo("\t\t<h3>Select column(s) from tables</h3>\n");
			if (isset($_SESSION['dbtable']))
			{
				echo("\t\t<form method='post' action='". htmlspecialchars($_SERVER['PHP_SELF']) ."'>\n");
				for ($i = 0; $i < count($_SESSION['dbtable']); $i++)
				{
					echo("\t\t\t<h4>Table ". $_SESSION['dbtable'][$i] ."</h4>\n");
					echo("\t\t\t<table>\n");
					for ($j = 0; $j < count($fields[strtolower($_SESSION['dbtable'][$i])]); $j++)
					{
						if ($j % 4 == 0) echo("\t\t\t\t<tr>\n");
						// Field 'pooledLibraryConcentration' of table 'run' is too large for one column, put last in multiple columns
						if (strcmp($_SESSION['dbtable'][$i], 'run') == 0 && $j == 13)
						{
							echo("\t\t\t\t\t<td class='check'><input type='checkbox' class='".$_SESSION['dbtable'][$i]."' name='".$_SESSION['dbtable'][$i]."[]' value='".$fields[$_SESSION['dbtable'][$i]][$j]."' ");
							if (isset($_SESSION['fields'][$_SESSION['dbtable'][$i]]) && array_search($fields[$_SESSION['dbtable'][$i]][$j], $_SESSION['fields'][$_SESSION['dbtable'][$i]]) !== false) echo("checked ");
							echo("/></td>\n");
							echo("\t\t\t\t\t<td colspan='3'>".$fields[$_SESSION['dbtable'][$i]][$j]."</td>\n");
							$j++;
						}
						else
						{
							echo("\t\t\t\t\t<td class='check'><input type='checkbox' class='".$_SESSION['dbtable'][$i]."' name='".$_SESSION['dbtable'][$i]."[]' value='".$fields[$_SESSION['dbtable'][$i]][$j]."' ");
							if (isset($_SESSION['fields'][$_SESSION['dbtable'][$i]]) && array_search($fields[$_SESSION['dbtable'][$i]][$j], $_SESSION['fields'][$_SESSION['dbtable'][$i]]) !== false) echo("checked ");
							echo("/></td>\n");
							echo("\t\t\t\t\t<td class='dbitem'>".$fields[$_SESSION['dbtable'][$i]][$j]."</td>\n");
						}
						if ($j % 4 == 3) echo("\t\t\t\t</tr>\n");
					}
					$j--;
					for ($j %= 4; $j < 3; $j++)
					{
						echo("\t\t\t\t\t<td class='check'></td><td class='dbitem'></td>\n");
						if ($j == 2) echo("\t\t\t\t</tr>\n"); 
					}
					echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='8'>\n\t\t\t\t\t\t<input name='". $_SESSION['dbtable'][$i] ."' class='checkbutton' type='button' value='Check all fields' />\n");
					echo("\t\t\t\t\t\t<input name='". $_SESSION['dbtable'][$i] ."' class='uncheckbutton' type='button' value='Uncheck all fields' />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n\t\t\t</table>\n");
				}
				echo("\t\t\t<table>\n\t\t\t\t<tr>\n\t\t\t\t\t<td><input class='ok' type='submit' name='fields' value='OK' /></td>\n\t\t\t\t</tr>\n\t\t\t</table>\n\t\t</form>\n");	
			}
			
			// Show possible aggregate functions (SQL: COUNT SUM AVERAGE ...)
			// This can be added as an extension to the application
			// For now this can be achieved by printing in Excel and applying the function in Excel
			
			// Show possible row selects (SQL: WHERE)
			echo("\t\t<h3>Select row(s) from tables</h3>\n");
			if (!empty($_SESSION['fields']))
			{
				echo("\t\t<form method='post' action='". htmlspecialchars($_SERVER['PHP_SELF']) ."'>\n");
				echo("\t\t\t<table>\n");
				for ($i = 0; $i < count($_SESSION['fields']); $i++)
				{
					for ($j = 0; $j < count($_SESSION['fields'][$_SESSION['dbtable'][$i]]); $j++)
					{
						$value = $_SESSION['dbtable'][$i].".".$_SESSION['fields'][$_SESSION['dbtable'][$i]][$j];
						echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'><input type='checkbox' name='rows[]' value='".$value."' ");
						if (isset($_SESSION['where']) && stristr($_SESSION['where'], $value) !== false) echo("checked ");
						echo("/></td>\n");
						echo("\t\t\t\t\t<td class='dbitem'>".$value."</td>\n");
						echo("\t\t\t\t\t<td colspan='2'>\n");
						echo("\t\t\t\t\t\t<select name='operator".$i."_".$j."'>\n");
						echo("\t\t\t\t\t\t\t<option value='eq'>&equals;</option><option value='neq'>&ne;</option>\n");
						echo("\t\t\t\t\t\t\t<option value='lt'>&lt;</option><option value='gt'>&gt;</option>\n");
						echo("\t\t\t\t\t\t\t<option value='le'>&le;</option><option value='ge'>&ge;</option>\n");
						echo("\t\t\t\t\t\t</select>\n\t\t\t\t\t</td>\n");
						echo("\t\t\t\t\t<td colspan='3'>\n");
						echo("\t\t\t\t\t\t<select class='orderby' name='value".$i."_".$j."'>\n");
						for ($k = 0; $k < count($_SESSION['fieldvalue'][$i][$j]); $k++)
						{
							echo("\t\t\t\t\t\t\t<option value='".$_SESSION['fieldvalue'][$i][$j][$k]."'>".htmlspecialchars($_SESSION['fieldvalue'][$i][$j][$k])."</option>\n");
						}
						echo("\t\t\t\t\t\t</select>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
						if ($i != (count($_SESSION['fields']) - 1) 
							|| ($i == (count($_SESSION['fields']) - 1) && $j != (count($_SESSION['fields'][$_SESSION['dbtable'][$i]]) - 1)))
						{
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='7'></td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='2'> </td>\n");
							echo("\t\t\t\t\t<td class='check'><input type='radio' name='andor".$i."_".$j."' value='AND' /></td>\n\t\t\t\t\t<td>AND</td>\n");
							echo("\t\t\t\t\t<td class='check'><input type='radio' name='andor".$i."_".$j."' value='OR' /></td>\n\t\t\t\t\t<td>OR</td>\n");
							echo("\t\t\t\t\t<td colspan='1'> </td>\n\t\t\t\t</tr>\n");
							echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='7'></td>\n\t\t\t\t</tr>\n");
						}
					}
				}
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='7'><input class='ok' type='submit' name='where' value='OK' /></td>\n\t\t\t\t</tr>\n\t\t\t</table>\n\t\t</form>\n");
			}
			
			// Options
			echo("\t\t<h3>Options</h3>\n");
			if (!empty($_SESSION['fields']))
			{
				echo("\t\t<form method='post' action='". htmlspecialchars($_SERVER['PHP_SELF']) ."'>\n");
				// Only distinct rows? (SQL: DISTINCT)
				echo("\t\t\t<table>\n\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='8'><b>Select only distinct rows?</b></td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'><input type='checkbox' name='distinct' value='checked' ");
				if ($_SESSION['distinct']) echo("checked ");
				echo("/></td>\n\t\t\t\t\t<td class='dbitem'>Only distinct rows</td>\n");
				echo("\t\t\t\t\t<td class='check'></td>\n\t\t\t\t\t<td class='dbitem'></td>\n\t\t\t\t\t<td class='check'></td>\n\t\t\t\t\t<td class='dbitem'></td>\n\t\t\t\t\t<td class='check'></td>\n\t\t\t\t\t<td class='dbitem'></td>\n\t\t\t\t</tr>\n");
				// Order results? (SQL: ORDER BY)
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='8'><b>Order results?</b></td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'><input type='checkbox' name='order' value='checked' ");
				if (!empty($_SESSION['order'])) echo("checked ");
				echo("/></td>\n\t\t\t\t\t<td class='dbitem'>Order results</td>\n");
				echo("\t\t\t\t\t<td class='check'><input type='radio' name='ordering' value='ASC'");
				if (empty($_SESSION['order']) || (!empty($_SESSION['order']) && strcmp($_SESSION['order'], 'ASC') == 0)) echo(" checked");
				echo("></td>\n\t\t\t\t\t<td class='dbitem'>Ascending</td>\n");
				echo("\t\t\t\t\t<td class='check'><input type='radio' name='ordering' value='DESC'");
				if (!empty($_SESSION['order']) && strcmp($_SESSION['order'], 'DESC') == 0) echo(" checked");
				echo("></td>\n\t\t\t\t\t<td class='dbitem'>Descending</td>\n");
				echo("\t\t\t\t\t<td class='check'></td><td class='dbitem'></td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td class='check'></td>\n");
				echo("\t\t\t\t\t<td colspan='7'>By:\n\t\t\t\t\t\t<select class='orderby' name='orderby'>\n");
				for ($i = 0; $i < count($_SESSION['fields']); $i++)
				{
					for ($j = 0; $j < count($_SESSION['fields'][$_SESSION['dbtable'][$i]]); $j++)
					{
						$value = $_SESSION['dbtable'][$i].".".$_SESSION['fields'][$_SESSION['dbtable'][$i]][$j];
						echo("\t\t\t\t\t\t\t<option value='". $value ."'");
						if (!empty($_SESSION['orderby']) && strcmp($_SESSION['orderby'], $value) == 0)
						{
							echo(" selected");
						}
						echo(">". $_SESSION['dbtable'][$i].".".$_SESSION['fields'][$_SESSION['dbtable'][$i]][$j] ."</option>\n");
					}
				}
				echo("\t\t\t\t\t\t</select>\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n");
				echo("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan='8'><input class='ok' type='submit' name='options' value='OK' /></td>\n\t\t\t\t</tr>\n\t\t\t</table>\n\t\t</form>\n");	
			}
						
			// Show query
			echo("\t\t<h3>Your query</h3>\n");
			echo("\t\t<form method='post' action='queryResults.php'>\n");
			echo("\t\t\t<textarea class='query' name='query' rows='7' disabled>\n");
			if (!empty($query)) echo($query);
			echo("\n\t\t\t</textarea>\n");
			echo("\t\t\t<div class='buttonBox'>\n");
			echo("\t\t\t\t<a class='buttonMarginRight' href='home.php'>Cancel</a>\n");
			echo("\t\t\t\t<a class='buttonMarginRight' href='queryClear.php'>Clear inputs</a>\n");
			echo("\t\t\t\t<input class='button' type='submit' value='Run query' />\n");
			echo("\t\t\t</div>\n\t\t</form>\n");
		}
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