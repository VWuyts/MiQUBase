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
	/*
	 * Define a Read Filter class implementing PHPExcel_Reader_IReadFilter
	 */
	class MyReadFilter implements PHPExcel_Reader_IReadFilter 
	{ 
		private $_startRow = 0; 
		private $_endRow   = 0; 
		private $_columns  = array(); 

		// Get the list of rows and columns to read
		public function __construct($startRow, $endRow, $columns)
		{ 
			$this->_startRow = $startRow; 
			$this->_endRow   = $endRow; 
			$this->_columns  = $columns; 
		}
		
		// Set the list of rows and columns to read
		public function setRow($startRow, $endRow, $columns)
		{
			$this->_startRow = $startRow; 
			$this->_endRow   = $endRow; 
			$this->_columns  = $columns; 
		}
		
		// Only read the rows and columns that were configured 
		public function readCell($column, $row, $worksheetName = '')
		{ 
			if ($row >= $this->_startRow && $row <= $this->_endRow)
			{ 
				if (in_array($column, $this->_columns))
				{ 
					return true; 
				} 
			}
			return false;
		}
	}
?>