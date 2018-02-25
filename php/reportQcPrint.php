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
	if (isset($_SESSION['report']) && isset($_SESSION['excel']) && isset($_SESSION['role'])
		&& (strcmp($_SESSION['role'], 'administrator') == 0 || strcmp($_SESSION['role'], 'executor') == 0 || strcmp($_SESSION['role'], 'readonly') == 0 || strcmp($_SESSION['role'], 'creator') == 0))
	{
		require 'functions.php';
		require 'logHandling.php';
		require 'errorHandling.php';
		require 'externalclasses/PHPExcel/PHPExcel.php';
		
		// Set variables
		$reportName = 'MiQUBase report: QC parameters';
		
		// Set variables for pdf
		if (!$_SESSION['excel'])
		{
			$rendererName = PHPExcel_Settings::PDF_RENDERER_TCPDF;
			$rendererLibrary = 'TCPDF';
			$rendererLibraryPath = 'externalclasses/' . $rendererLibrary;
			if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath))
			{
				unset($_SESSION['report']);
				unset($_SESSION['excel']);
				trigger_error('018@'.$_SESSION['user'].'@'.__FILE__.'@'.__LINE__.'@0@'.$rendererLibraryPath, E_USER_ERROR);
				die();
			}
		}
		// Create PHPExcel object;
		$objPHPExcel = new PHPExcel();
		// Set document properties
		$objPHPExcel->getProperties()->setCreator($_SESSION['user'])
									 ->setLastModifiedBy($_SESSION['user'])
									 ->setTitle("QC parameters");
		// Set font
		$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
		// Add the PBB header
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('B1', 'Platform Biotechnology and Molecular Biology')
					->setCellValue('B2', $reportName);
		$objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('B1:B2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->mergeCells('A1:A2');
		$objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
		$objPHPExcel->getActiveSheet()->mergeCells('B2:E2');
		$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(26);
		$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(40);
		$objPHPExcel->getActiveSheet()->getStyle('B1:E2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('B1:E2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		if ($_SESSION['excel'])
		{
			$style = array(
					'borders' => array(
						'outline' => array(
							'style' => PHPExcel_Style_Border::BORDER_THIN,
							'color' => array('argb' => 'FF000000'),
						),
					),
			);
			$objPHPExcel->getActiveSheet()->getStyle('B1:E2')->applyFromArray($style);
			$objPHPExcel->getActiveSheet()->getStyle('A1:A2')->applyFromArray($style);
		}
		$objDrawing = new PHPExcel_Worksheet_Drawing();
		$objDrawing->setDescription('WIV-ISP logo');
		$objDrawing->setPath('../images/logowivisp.png');
		$objDrawing->setHeight(70);
		$objDrawing->setCoordinates('A1');
		$objDrawing->setOffsetX(15);
		$objDrawing->setOffsetY(5);
		$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		// Add the general data
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A4', 'Report date:')
					->setCellValue('B4', date('d/m/Y'))
					->setCellValue('A6', 'Analyst:')
					->setCellValue('B6', $_SESSION['initials']);
		$objPHPExcel->getActiveSheet()->getStyle('A4:A6')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('B4:B8')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle('E8')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		// Add the data
		$objPHPExcel->getActiveSheet()->mergeCells('A29:E33');
		$objPHPExcel->getActiveSheet()->getStyle('B20')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle('A29:E33')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		$objPHPExcel->getActiveSheet()->getStyle('A27:E33')->getFont()->setSize(10);
		$objPHPExcel->getActiveSheet()->getStyle('B13')->getNumberFormat()->setFormatCode('0.00');
		$objPHPExcel->getActiveSheet()->getStyle('B16')->getNumberFormat()->setFormatCode('0');
		$objPHPExcel->getActiveSheet()->getStyle('B18')->getNumberFormat()->setFormatCode('0.00');
		$objPHPExcel->getActiveSheet()->getStyle('B20')->getNumberFormat()->setFormatCode('0.000');
		$objPHPExcel->getActiveSheet()->getStyle('B22')->getNumberFormat()->setFormatCode('0.0');
		$objPHPExcel->getActiveSheet()->getStyle('B25')->getNumberFormat()->setFormatCode('0.0');
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A8', 'Run number:')
					->setCellValue('B8', $_SESSION['report']['runnumber'])
					->setCellValue('D8', 'MiSeq reagent kit:')
					->setCellValue('E8', $_SESSION['report']['seqCartridge'])
					->setCellValue('A13', 'Average Q score (%):')
					->setCellValue('B13', $_SESSION['report']['qScore'])
					->setCellValue('A16', 'Cluster density (K/mm²):')
					->setCellValue('B16', $_SESSION['report']['clusterDensity'])
					->setCellValue('A18', 'Clusters passing filter (%):')
					->setCellValue('B18', $_SESSION['report']['clustersPF'])
					->setCellValue('A20', 'Phasing/Prephasing (max %):')
					->setCellValue('B20', $_SESSION['report']['phasing'] ."/". $_SESSION['report']['prephasing'])
					->setCellValue('A22', 'Reads passing filter (millions):')
					->setCellValue('B22', $_SESSION['report']['readsPF'])
					->setCellValue('A25', 'Aligned [to PhiX control] (%):')
					->setCellValue('B25', $_SESSION['report']['aligned'])
					->setCellValue('A27', 'Remarks:')
					->setCellValue('A29', $_SESSION['report']['remark']);
		$objPHPExcel->getActiveSheet()->getStyle('A8:A27')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('D8')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(28);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(23);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(22);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
		// Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle('QC_parameters');
		if (!$_SESSION['excel'])
		{
			$objPHPExcel->getActiveSheet()->setShowGridLines(false);
		}
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		// Create file name
		$filename = "QCparameters_". $_SESSION['report']['runnumber'] ."_". date("Ymd") ."_". $_SESSION['user'];
		
		// Print to Excel
		if ($_SESSION['excel'])
		{
			// Add extension xlsx to file name
			$filename .= ".xlsx";
			// Redirect output to web browser (Excel2007: xlsx)
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
			header("Content-Disposition: attachment;filename=$filename");
			header("Cache-Control: max-age=0");
			// If serving to IE over SSL, then the following may be needed
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); // always modified
			header("Cache-Control: cache, must-revalidate"); // HTTP/1.1
			header("Pragma: public"); // HTTP/1.0
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output');
			// Create log message
			$activitylogger->info($reportName .' printed to Excel', ['user'=>$_SESSION['user'], 'runnumber'=>$_SESSION['report']['runnumber']]);
		}
		// Print to pdf
		else
		{
			// Add extension pdf to file name
			$filename .= ".pdf";
			// Redirect output to web browser (PDF)
			header("Content-Type: application/pdf");
			header("Content-Disposition: attachment;filename=$filename");
			header("Cache-Control: max-age=0");
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
			$objWriter->save('php://output');
			// Create log message
			$activitylogger->info($reportName .' printed to pdf', ['user'=>$_SESSION['user'], 'runnumber'=>$_SESSION['report']['runnumber']]);
		}
		
		// Unset variables
		unset($_SESSION['report']);
		unset($_SESSION['excel']);
	}
	else
	{
		// Session variable isn't registered or role does not comply: user should not be on this page
		session_unset();
		session_destroy();
		header("Location: ../index.php");
	}
?>