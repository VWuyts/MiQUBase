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
	if (isset($_SESSION['inputArr']) && isset($_SESSION['role'])
		&& (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		require 'externalclasses/PHPExcel/PHPExcel.php';
		require 'externalclasses/PHPExcel/PHPExcel/IOFactory.php';
		require 'classes.php';
		createHead(true, 'MiQUBase sample overview', ['actions'], null);
		createHeader($_SESSION['user'], true);
		
		// Set database connection
		if (($dbconn = pg_connect($_SESSION['connString'])) === false)
		{
			if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
			{
				$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
			}
			unset($_SESSION['inputArr']);
			trigger_error('005@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
			die();
		}
		else
		{
			// Create arrays for database input
			$lane = [
				'lanenumber'		=> null,
				'runid'				=> $_SESSION['inputArr']['runId'],
				'tiles'				=> null,
				'totalreads'		=> null,
				'readspf'			=> null,
				'readsidentifiedpf'	=> null,
				'cv'				=> null,
			];
			$ngsread = [
				'laneid'				=> null,
				'readnumber'			=> null,
				'isindexedread'			=> null,
				'density'				=> null,
				'density_sd'			=> null,
				'clusterpf'				=> null,
				'clusterpf_sd'			=> null,
				'phasing'				=> null,
				'prephasing'			=> null,
				'noreads'				=> null,
				'noreadspf'				=> null,
				'q30'					=> null,
				'yield'					=> null,
				'cycleserrrated'		=> null,
				'aligned'				=> null,
				'aligned_sd'			=> null,
				'errorrate'				=> null,
				'errorrate_sd'			=> null,
				'errorrate35'			=> null,
				'errorrate35_sd'		=> null,
				'errorrate75'			=> null,
				'errorrate75_sd'		=> null,
				'errorrate100'			=> null,
				'errorrate100_sd'		=> null,
				'intensitycycle1'		=> null,
				'intensitycycle1_sd'	=> null,
			];
			$sample = [
				'samplename'		=> null,
				'speciesid'			=> null,
				'projectid'			=> null,
				'laneid'			=> null,
				'receptiondate'		=> null,
				'sop'				=> null,
				'priority'			=> null,
				'isrepeatof'		=> null,
				'r_d'				=> null,
				'indexnumber'		=> null,
				'index1_i7'			=> null,
				'index2_i5'			=> null,
				'readsidentifiedpf'	=> null,
				'remark'			=> null,
			];
			$summaryTotal = [
				'laneid'			=> null,
				'isnonindexedtotal'	=> null,
				'yieldtotal'		=> null,
				'aligned'			=> null,
				'errorrate'			=> null,
				'intensitycycle1'	=> null,
				'q30'				=> null,
			];
			// Declare arrays to collect samples, ngsReads and summaryTotal
			$sampleArr = array();
			$ngsReadArr = array();
			$summaryTotalArr = array();
			// Declare arrays with outputs for overview
			$samplename = array();
			$species = array();
			$division = array();
			$project = array();
			$qcParameters = [
				'qScore'			=> null,
				'clusterDensity'	=> null,
				'clustersPF'		=> null,
				'phasing'			=> null,
				'prephasing'		=> null,
				'readsPF'			=> null,
				'aligned'			=> null,
			];
			// Create counter to check if the number of samples in the Request FORM (length of $sampleArr) corresponds
			// to the number of samples in the Excel copy of the SAV index tab ($noSamplesIndex)
			$noSamplesIndex = 0;
						
			// Set up Excel readers
			try
			{
				// Excel extension is xlsx
				$filetype = 'Excel2007';
				$requestSheet = '01-ListDetails';
				// Different readers for each file
				$requestReader = PHPExcel_IOFactory::createReader($filetype);
				$indexReader = PHPExcel_IOFactory::createReader($filetype);
				$summaryReader = PHPExcel_IOFactory::createReader($filetype);
				// Set readers to read-only
				$requestReader->setReadDataOnly(true);
				$indexReader->setReadDataOnly(true);
				$summaryReader->setReadDataOnly(true);
				// Request FORM: only sheet '01_ListDetails' needed
				$requestReader->setLoadSheetsOnly($requestSheet);
			}
			catch (PHPExcel_Reader_Exception $e)
			{
				global $errorlogger;
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
				{
					$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				$errorlogger->error('PHPExcel failed', ['user'=>$_SESSION['user'], 'errstr'=>$e->getMessage(), 'errfile'=>__FILE__, 'errline'=>__LINE__]);
				createErrorPage(['Reading the excel files failed.', 'Please contact the MiQUBase administrator.']);
				die();
			}
			// Parse Request FORM
			try
			{
				// Data in '01_ListDetails' start at row 8 and are contained in columns C up to N
				$currentRow = 8;
				$columns = range('C','N');
				// Keep track of sop and division as sop is filled out once per division, unless otherwise indicated
				$currentSop = $currentDivision = null;
				// Set up read filter to read one row of data at a time of the Request FORM
				$filterSubset = new MyReadFilter($currentRow, $currentRow, $columns);
				$requestReader->setReadFilter($filterSubset);
				// Load 1 row of data
				$objPHPExcel = $requestReader->load($_SESSION['inputArr']['requestForm']);
				// Put data in array with public function toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
				$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
				// Load samples while sample name (column B) is not empty
				while (!empty($data[$currentRow]['C']))
				{
					// Fill sample array
					$sample['samplename'] = $data[$currentRow]['C'];
					array_push($samplename, $sample['samplename']);
					//end samplename
					if (empty($data[$currentRow]['D']))
					{
						pg_close($dbconn);
						if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
						{
							$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
						}
						unset($_SESSION['inputArr']);
						trigger_error('011@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$query = "SELECT speciesid
							FROM species
							WHERE upper(speciesname) = upper($1)";
						$result = pg_query_params($dbconn, $query, [$data[$currentRow]['D']]);
						if (!$result)
						{
							pg_close($dbconn);
							if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
							{
								$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
							}
							unset($_SESSION['inputArr']);
							trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						elseif (pg_num_rows($result) == 0)
						{
							pg_close($dbconn);
							if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
							{
								$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
							}
							unset($_SESSION['inputArr']);
							trigger_error('012@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							if (!($sample['speciesid'] = pg_fetch_result($result, 'speciesid')))
							{
								pg_close($dbconn);
								if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
								{
									$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
								}
								unset($_SESSION['inputArr']);
								trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
								die();
							}
							else
							{
								array_push($species, $data[$currentRow]['D']);
							}
						}
					}//end speciesid
					if (empty($data[$currentRow]['H']))
					{
						pg_close($dbconn);
						if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
						{
							$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
						}
						unset($_SESSION['inputArr']);
						trigger_error('011@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$query = "SELECT project.projectid, project.divisionid, division.divisionname
							FROM project
								LEFT JOIN division
								ON project.divisionid = division.divisionid
							WHERE upper(projectnumber) = upper($1) AND project.isactive = TRUE";
						$result = pg_query_params($dbconn, $query, [$data[$currentRow]['H']]);
						if (!$result)
						{
							pg_close($dbconn);
							if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
							{
								$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
							}
							unset($_SESSION['inputArr']);
							trigger_error('006@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						elseif (pg_num_rows($result) == 0)
						{
							pg_close($dbconn);
							if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
							{
								$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
							}
							unset($_SESSION['inputArr']);
							trigger_error('012@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
						else
						{
							if (!($sample['projectid'] = pg_fetch_result($result, 'projectid')))
							{
								pg_close($dbconn);
								if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
								{
									$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
								}
								unset($_SESSION['inputArr']);
								trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
								die();
							}
							else
							{
								if (!($divisionid = pg_fetch_result($result, 'divisionid')))
								{
									pg_close($dbconn);
									if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
									{
										$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
									}
									unset($_SESSION['inputArr']);
									trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
									die();
								}
								elseif (empty($currentDivision) || ($currentDivision != $divisionid))
								{
									$currentDivision = $divisionid;
									$currentSop = null;
								}
								if (!($divisionname = pg_fetch_result($result, 'divisionname')))
								{
									pg_close($dbconn);
									if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
									{
										$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
									}
									unset($_SESSION['inputArr']);
									trigger_error('008@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
									die();
								}
								else
								{
									array_push($project, $data[$currentRow]['H']);
									array_push($division, $divisionname);
								}
							}
						}
					}//end projectid
					if (empty($data[$currentRow]['N']))
					{
						$sample['sop'] = $currentSop;
					}
					else
					{
						$sample['sop'] = $data[$currentRow]['N'];
						$currentSop = $sample['sop'];
					}//end sop
					if (empty($data[$currentRow]['J']))
					{
						$sample['priority'] = 'normal';
					}
					else
					{
						$sample['priority'] = $data[$currentRow]['J'];
					}//end priority
					if (strcmp($data[$currentRow]['M'], 'X') == 0)
					{
						$sample['r_d'] = true;
					}//end r&d
					
					// Add sample array to sample collection and clear sample array
					array_push($sampleArr, $sample);
					$sample = array_fill_keys(array_keys($sample), null);
					// Read data of next row
					$currentRow++;
					$filterSubset->setRow($currentRow, $currentRow, $columns);
					$objPHPExcel = $requestReader->load($_SESSION['inputArr']['requestForm']);
					$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
				}
			}
			catch (PHPExcel_Reader_Exception $e)
			{
				global $errorlogger;
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
				{
					$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				$errorlogger->error('PHPExcel failed parsing input files', ['user'=>$_SESSION['user'], 'errstr'=>$e->getMessage(), 'errfile'=>__FILE__, 'errline'=>__LINE__]);
				createErrorPage(['Parsing of Excel files failed.', 'Please contact the MiQUBase administrator.']);
				die();
			}//end parsing Request FORM
			// Parse SAV index tab
			try
			{
				// Lane data in SAV index tab start at row 2 and are contained in columns A up to D
				$currentRow = 2;
				$columns = range('A','D');
				// Set up read filter to read one row of data at a time of the SAV index tab
				$filterSubset = new MyReadFilter($currentRow, $currentRow, $columns);
				$indexReader->setReadFilter($filterSubset);
				// Load 1 row of data
				$objPHPExcel = $indexReader->load($_SESSION['inputArr']['savIndex']);
				// Put data in array with public function toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
				$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
				// Load lane data
				if (isset($data[$currentRow]['A']) && isset($data[$currentRow]['B']) && isset($data[$currentRow]['C']) && isset($data[$currentRow]['D']))
				{
					$lane['totalreads'] = $data[$currentRow]['A'];
					$lane['readspf'] = $data[$currentRow]['B'];
					$lane['readsidentifiedpf'] = $data[$currentRow]['C'];
					$lane['cv'] = $data[$currentRow]['D'];
				}
				else
				{
					pg_close($dbconn);
					if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
					{
						$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
					}
					unset($_SESSION['inputArr']);
					trigger_error('013@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}				
				// Sample data in SAV index tab start at row 4 and are contained in columns A up to F
				$currentRow = 4;
				$columns = range('A','F');
				$filterSubset->setRow($currentRow, $currentRow, $columns);
				// Load 1 row of data
				$objPHPExcel = $indexReader->load($_SESSION['inputArr']['savIndex']);
				// Put data in array with public function toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
				$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
				// Load samples while sample name (column B) is not empty and sample name corresponds to those in Request FORM
				while (!empty($data[$currentRow]['B']))
				{
					$index = array_search($data[$currentRow]['B'], $samplename);
					if ($index === false)
					{
						pg_close($dbconn);
						if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
						{
							$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
						}
						unset($_SESSION['inputArr']);
						trigger_error('014@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
					else
					{
						$noSamplesIndex++;
						if (isset($data[$currentRow]['A']) && !empty($data[$currentRow]['D']) && !empty($data[$currentRow]['E']) && isset($data[$currentRow]['F']))
						{
							$sampleArr[$index]['indexnumber'] = $data[$currentRow]['A'];
							$sampleArr[$index]['index1_i7'] = $data[$currentRow]['D'];
							$sampleArr[$index]['index2_i5'] = $data[$currentRow]['E'];
							$sampleArr[$index]['readsidentifiedpf'] = $data[$currentRow]['F'];
							// Read data of next row
							$currentRow++;
							$filterSubset->setRow($currentRow, $currentRow, $columns);
							$objPHPExcel = $indexReader->load($_SESSION['inputArr']['savIndex']);
							$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
						}
						else
						{
							pg_close($dbconn);
							if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
							{
								$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
							}
							unset($_SESSION['inputArr']);
							trigger_error('013@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
							die();
						}
					}
				}
				if (count($samplename) != $noSamplesIndex)
				{
					pg_close($dbconn);
					if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
					{
						$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
					}
					unset($_SESSION['inputArr']);
					trigger_error('019@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
			}
			catch (PHPExcel_Reader_Exception $e)
			{
				global $errorlogger;
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
				{
					$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				$errorlogger->error('PHPExcel failed parsing input files', ['user'=>$_SESSION['user'], 'errstr'=>$e->getMessage(), 'errfile'=>__FILE__, 'errline'=>__LINE__]);
				createErrorPage(['Parsing of Excel files failed.', 'Please contact the MiQUBase administrator.']);
				die();
			}//end parsing SAV index tab
			// Parse SAV summary tab
			try
			{
				// SummaryTotal data in SAV summary tab start at row 8 and are contained in columns B up to G
				$currentRow = 8;
				$columns = range('B','G');
				// Set up read filter to read 2 rows of data of the SAV summary tab
				$filterSubset = new MyReadFilter($currentRow, $currentRow + 1, $columns);
				$summaryReader->setReadFilter($filterSubset);
				// Load 1 row of data
				$objPHPExcel = $summaryReader->load($_SESSION['inputArr']['savSummary']);
				// Put data in array with public function toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
				$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
				// Parse summaryTotal data
				if (isset($data[$currentRow]['B']) && isset($data[$currentRow]['D']) && isset($data[$currentRow]['E']) && isset($data[$currentRow]['F']) && isset($data[$currentRow]['G'])
					&& isset($data[$currentRow + 1]['B']) && isset($data[$currentRow + 1]['D']) && isset($data[$currentRow + 1]['E']) && isset($data[$currentRow + 1]['F']) && isset($data[$currentRow + 1]['G']))
				{
					// Parse data of non-indexed total
					$summaryTotal['isnonindexedtotal'] = true;
					$summaryTotal['yieldtotal'] = $data[$currentRow]['B'];
					$summaryTotal['aligned'] = $data[$currentRow]['D'];
					$summaryTotal['errorrate'] = $data[$currentRow]['E'];
					$summaryTotal['intensitycycle1'] = $data[$currentRow]['F'];
					$summaryTotal['q30'] = $data[$currentRow]['G'];
					// Add non-indexed total to summaryTotalArr
					array_push($summaryTotalArr, $summaryTotal);
					// Set currentRow to row of total (index + non-indexed)
					$currentRow++;
					// Parse data of total (index + non-indexed)
					$summaryTotal['isnonindexedtotal'] = false;
					$summaryTotal['yieldtotal'] = $data[$currentRow]['B'];
					$summaryTotal['aligned'] = $data[$currentRow]['D'];
					$summaryTotal['errorrate'] = $data[$currentRow]['E'];
					$summaryTotal['intensitycycle1'] = $data[$currentRow]['F'];
					$summaryTotal['q30'] = $data[$currentRow]['G'];
					// Add required data to qcParameters array
					$qcParameters['qScore'] = $summaryTotal['q30'];
					$qcParameters['aligned'] = $summaryTotal['aligned'];
					// Add total (index + non-indexed) to summaryTotalArr
					array_push($summaryTotalArr, $summaryTotal);
				}
				else
				{
					pg_close($dbconn);
					if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
					{
						$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
					}
					unset($_SESSION['inputArr']);
					trigger_error('015@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
					die();
				}
				// ngsRead data in SAV summary tab start at row 14 and are contained in columns A up to P
				$columns = range('A','P');
				// Counter to keep track of reads
				$counter = 0;
				// Parse data of ngsReads
				for ($currentRow = 14; $currentRow < 30; $currentRow += 5)
				{
					$counter++;
					$filterSubset->setRow($currentRow, $currentRow, $columns);
					// Load data of 1 Read
					$objPHPExcel = $summaryReader->load($_SESSION['inputArr']['savSummary']);
					// Put data in array with public function toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
					$data = $objPHPExcel->getActiveSheet()->toArray(null,false,false,true);
					// Parse data of Read
					if (isset($data[$currentRow]['A']) && isset($data[$currentRow]['B']) && !empty($data[$currentRow]['C']) && !empty($data[$currentRow]['D']) && !empty($data[$currentRow]['E']) && isset($data[$currentRow]['F'])
						&& isset($data[$currentRow]['G']) && isset($data[$currentRow]['H']) && isset($data[$currentRow]['I']) && isset($data[$currentRow]['J']) && !empty($data[$currentRow]['K'])
						&& !empty($data[$currentRow]['L']) && !empty($data[$currentRow]['M']) && !empty($data[$currentRow]['N']) && !empty($data[$currentRow]['O']) && !empty($data[$currentRow]['P']))
					{
						$ngsread['readnumber'] = $counter;
						if ($counter == 1 || $counter == 4)
						{
							$ngsread['isindexedread'] = false;
						}
						else
						{
							$ngsread['isindexedread'] = true;
						}
						$valuesArr = explode(' +/- ',  $data[$currentRow]['C']);
						$ngsread['density'] = $valuesArr[0];
						$ngsread['density_sd'] = $valuesArr[1];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['D']);
						$ngsread['clusterpf'] = $valuesArr[0];
						$ngsread['clusterpf_sd'] = $valuesArr[1];
						$valuesArr = explode(' / ',  $data[$currentRow]['E']);
						$ngsread['phasing'] = $valuesArr[0];
						$ngsread['prephasing'] = $valuesArr[1];
						$ngsread['noreads'] = $data[$currentRow]['F'];
						$ngsread['noreadspf'] = $data[$currentRow]['G'];
						$ngsread['q30'] = $data[$currentRow]['H'];
						$ngsread['yield'] = $data[$currentRow]['I'];
						$ngsread['cycleserrrated'] = $data[$currentRow]['J'];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['K']);
						$ngsread['aligned'] = $valuesArr[0];
						$ngsread['aligned_sd'] = $valuesArr[1];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['L']);
						$ngsread['errorrate'] = $valuesArr[0];
						$ngsread['errorrate_sd'] = $valuesArr[1];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['M']);
						$ngsread['errorrate35'] = $valuesArr[0];
						$ngsread['errorrate35_sd'] = $valuesArr[1];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['N']);
						$ngsread['errorrate75'] = $valuesArr[0];
						$ngsread['errorrate75_sd'] = $valuesArr[1];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['O']);
						$ngsread['errorrate100'] = $valuesArr[0];
						$ngsread['errorrate100_sd'] = $valuesArr[1];
						$valuesArr = explode(' +/- ',  $data[$currentRow]['P']);
						$ngsread['intensitycycle1'] = $valuesArr[0];
						$ngsread['intensitycycle1_sd'] = $valuesArr[1];
						// Add ngsRead to ngsReadArr
						array_push($ngsReadArr, $ngsread);
						// Parse data of lane (is ok to be overwritten)
						$lane['lanenumber'] = $data[$currentRow]['A'];
						$lane['tiles'] = $data[$currentRow]['B'];
					}
					else
					{
						pg_close($dbconn);
						if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
						{
							$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
						}
						unset($_SESSION['inputArr']);
						trigger_error('015@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0', E_USER_ERROR);
						die();
					}
				}
				// Add required data to qcParameters array
				$qcParameters['clusterDensity'] = $ngsReadArr[0]['density'];
				$qcParameters['clustersPF'] = $ngsReadArr[0]['clusterpf'];
				$index = 0;
				for ($i = 1; $i < count($ngsReadArr); $i++)
				{
					if ($ngsReadArr[$i]['phasing'] > $ngsReadArr[$index]['phasing'])
					{
						$index = $i;
					}
				}
				$qcParameters['phasing'] = $ngsReadArr[$index]['phasing'];
				$qcParameters['prephasing'] = $ngsReadArr[$index]['prephasing'];
				$qcParameters['readsPF'] = $ngsReadArr[0]['noreadspf'];
			}
			catch (PHPExcel_Reader_Exception $e)
			{
				global $errorlogger;
				pg_close($dbconn);
				if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
				{
					$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
				}
				unset($_SESSION['inputArr']);
				$errorlogger->error('PHPExcel failed parsing input files', ['user'=>$_SESSION['user'], 'errstr'=>$e->getMessage(), 'errfile'=>__FILE__, 'errline'=>__LINE__]);
				createErrorPage(['Parsing of Excel files failed.', 'Please contact the MiQUBase administrator.']);
				die();
			}//end parsing SAV summary tab
		}
		// Delete uploaded files
		if (!unlink($_SESSION['inputArr']['requestForm']) || !unlink($_SESSION['inputArr']['savIndex']) || !unlink($_SESSION['inputArr']['savSummary']))
		{
			$errorlogger->error('deleting uploaded file(s) failed', ['user'=>$_SESSION['user'], 'file'=>__FILE__, 'line'=>__LINE__]);
		}
		// Add required arrays to session variable
		$_SESSION['inputArr']['lane'] = $lane;
		$_SESSION['inputArr']['sampleArr'] = $sampleArr;
		$_SESSION['inputArr']['ngsReadArr'] = $ngsReadArr;
		$_SESSION['inputArr']['summaryTotalArr'] = $summaryTotalArr;
		$_SESSION['inputArr']['samplename'] = $samplename;
		$_SESSION['inputArr']['species'] = $species;
		$_SESSION['inputArr']['division'] = $division;
		$_SESSION['inputArr']['project'] = $project;
		// Add arrays required in addSampleDate.php to session variable
		$_SESSION['inputArr']['noSamplesForDate'] = array();
		$_SESSION['inputArr']['receptionDates'] = array();
		// Show overview
		echo("\t\t<h1>Overview of samples to add to run ". $_SESSION['inputArr']['runNumber'] ." in MiQUBase</h1>\n");
?>
		<h3>Samples</h3>
		<table>
	<?php
		$currentDivision = $division[0];
		$currentProject = $project[0];
		$currentSpecies = $species[0];
		$counter = 0;
		echo("\t\t<tr>\n\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
		echo("\t\t\t\t<td colspan='3'>". $currentDivision ."</td>\n\t\t\t</tr>\n");
		echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Project:</td>\n");
		echo("\t\t\t\t<td colspan='3'>". $currentProject ."</td>\n\t\t\t</tr>\n");
		echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Species:</td>\n");
		echo("\t\t\t\t<td colspan='3'>". $currentSpecies ."</td>\n\t\t\t</tr>\n");
		echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Samples:</td>\n");
		for ($i = 0; $i < count($sampleArr); $i++)
		{
			$counter++;
			if (strcmp($currentDivision, $division[$i]) == 0 && strcmp($currentProject, $project[$i]) == 0 && strcmp($currentSpecies, $species[$i]) == 0)
			{
				if ($counter != 1 && $counter % 3 == 1)
				{
					echo("\t\t\t<tr>\n\t\t\t\t<td></td>\n");
				}
				echo("\t\t\t\t<td>". $sampleArr[$i]['samplename'] ."</td>\n");
				if ($counter % 3 == 0)
				{
					echo("\t\t\t</tr>\n");
				}
			}
			else
			{
				if ($counter % 3 != 1)
				{
					for ($j = 3 - (($counter - 1) % 3); $j > 0; $j--)
					{
						echo("\t\t\t\t<td>&nbsp;</td>\n");
					}
					echo("\t\t\t</tr>\n");
				}
				$currentDivision = $division[$i];
				$currentProject = $project[$i];
				$currentSpecies = $species[$i];
				echo("\t\t\t<tr>\n\t\t\t\t<td colspan='4'></td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Scientific unit:</td>\n");
				echo("\t\t\t\t<td colspan='3'>". $currentDivision ."</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Project unit:</td>\n");
				echo("\t\t\t\t<td colspan='3'>". $currentProject ."</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Species:</td>\n");
				echo("\t\t\t\t<td colspan='3'>". $currentSpecies ."</td>\n\t\t\t</tr>\n");
				echo("\t\t\t<tr>\n\t\t\t\t<td class='attribute'>Samples:</td>\n");
				echo("\t\t\t\t<td>". $sampleArr[$i]['samplename'] ."</td>\n");
				$counter = 1;
			}
		}
		if ($counter % 3 != 0)
		{
			for ($j = 3 - (($counter) % 3); $j > 0; $j--)
			{
				echo("\t\t\t\t<td>&nbsp;</td>\n");
			}
			echo("\t\t\t</tr>\n");
		}
	?>
		</table>
		<h3>QC parameters</h3>
		<table>
			<tr>
				<td class="attribute">Average Q score &lpar;&percnt;&rpar;:</td>
				<td><?php echo $qcParameters['qScore']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Cluster density &lpar;K&sol;mm&sup2;&rpar;:</td>
				<td><?php echo $qcParameters['clusterDensity']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Clusters passing filter &lpar;&percnt;&rpar;:</td>
				<td><?php echo $qcParameters['clustersPF']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Phasing&sol;Prephasing &lpar;max &percnt;&rpar;:</td>
				<td><?php echo $qcParameters['phasing'] ."&sol;". $qcParameters['prephasing']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Reads passing filter (millions):</td>
				<td><?php echo $qcParameters['readsPF']; ?></td>
			</tr>
			<tr>
				<td class="attribute">Aligned &lsqb;to PhiX control&rsqb; &lpar;&percnt;&rpar;:</td>
				<td><?php echo $qcParameters['aligned']; ?></td>
			</tr>
		</table>
		<form method="post" action="addSampleDate.php">
			<div class="buttonBox">
				<a class="buttonMarginRight" href="home.php">Cancel</a>
				<a class="buttonMarginRight" href="addSample.php">Previous</a>
				<input class="button" type="submit" name="continue" value="Continue" />
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
		header( "Location: ../index.php" );
	}
?>