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
	 * Function createFooter creates the default MiQUBase footer.
	 * Parameter inDirPhp: boolean; is the script calling the function located in the php folder
	 */
	function createFooter($inDirPhp)
	{
		echo("\t</div> <!--end content-->\n\n");
		echo( "\t<footer>\n");
		echo("\t\t<div id='footerright'>\n");
			if ($inDirPhp) echo("\t\t\t<img id='logotm' src='../images/tm_vignet_web.png' alt='logo Thomas More' />\n");
			else echo("\t\t\t<img id='logotm' src='images/tm_vignet_web.png' alt='logo Thomas More' />\n");
			echo("\t\t\t<p>Copyright &copy; 2017-2018 V&eacute;ronique Wuyts</p>\n");
			echo("\t\t\t<p>Professionele Bachelor Elektronica-ICT</p>\n");
			echo("\t\t\t<p>Thomas More Mechelen-Antwerpen vzw &ndash; Campus De Nayer</p>\n");
		echo("\t\t</div>\n");
		echo("\t\t<div id='footercenter'>\n");
			if ($inDirPhp) echo("\t\t\t<img id='logomiqubase' src='../images/logomiqubase_white.svg' alt='logo MiQUBase' />\n");
			else echo("\t\t\t<img id='logomiqubase' src='images/logomiqubase_white.svg' alt='logo MiQUBase' />\n");
			echo("\t\t\t<p>Last update 22/08/2018</p>\n");
		echo("\t\t</div>\n");
		echo("\t\t<p class='spacer'></p>\n");
		echo("\t</footer>\n");
		echo("</div>\n");
		echo("</body>\n");
		echo("</html>\n");
	}//end createFooter
	
	/*
	 * Function createHead creates the default MiQUBase HTML head.
	 * Parameter inDirPhp: boolean; is the script calling the function located in the php or message folder
	 * 			 title: string; title for the HTML page
	 *			 cssArray: array of strings; additional css files to be loaded
	 *			 script: string; JavaScript initialise function, null if to be omitted
	 */
	function createHead($inDirPhp, $title, $cssArray, $script)
	{
		$dir = '';
		if ($inDirPhp) $dir = '../';
		echo("<!doctype html>\n");
		echo("<html>\n");
		echo("<head>\n");
		echo("\t<meta charset='utf-8'>\n");
		echo("\t<title>$title</title>\n");
		echo("\t<link href='".$dir."favicon.ico' rel='icon' type='image/ico' />\n");
		echo("\t<link href='".$dir."stylesheets/reset_v2.css' rel='stylesheet' type='text/css' />\n");
		echo("\t<link href='".$dir."stylesheets/miqubase.css' rel='stylesheet' type='text/css' />\n");
		if (!is_null($cssArray)) // count(NULL) emits a warning as of PHP 7.2
		{
			for ($i = 0; $i < count($cssArray); $i++)
			{
				echo("\t<link href='".$dir."stylesheets/".$cssArray[$i].".css' rel='stylesheet' type='text/css' />\n");
			}
		}
		if (!is_null($script))
		{
			echo("\t<script>\n\t\tfunction initialise(){\n");
			echo($script);
			echo("\n\t\t}\n\t</script>\n");
		}
		echo("</head>\n\n");
		echo("<body");
		if (!is_null($script))
		{
			echo(" onLoad='initialise();'");
		}
		echo(">\n<div id='wrapper'>\n");
	}//end createHead

	/*
	 * Function createHeader creates the default MiQUBase header division.
	 * Parameter user: string; the user that is logged in, null if no user is logged in, false if no user or links have to be shown
	 *			 inDirPhp: boolean; is the script calling the function located in the php folder
	 */
	function createHeader($user, $inDirPhp)
	{
		echo("\t<header>\n");
		$dir = '';
		if (!$inDirPhp) $dir = '../php/';
		if ($user || $user === null)
		{
			echo("\t\t<img id='logowiv' src='../images/logowivisp_en.png' alt='Logo WIV-ISP' />\n");
			echo("\t\t<div id='nav'>\n");
			echo("\t\t\t<ul>\n");
			if ($user !== null) echo("\t\t\t\t<li class='link marginRight'><a href='".$dir."logout.php'>Logout</a></li>\n");
			echo("\t\t\t\t<li class='link". ($user === null ? " marginRight" : "") ."'><a href='".$dir."home.php'>Home</a></li>\n");
			if ($user !== null) echo("\t\t\t\t<li id='user'>$user</li>\n");
		}
		else
		{
			echo("\t\t<img id='logowiv' src='images/logowivisp_en.png' alt='Logo WIV-ISP' />\n");
			echo("\t\t<div id='nav'>\n");
			echo("\t\t\t<ul>\n");
			echo("\t\t\t\t<li>&nbsp;</li>\n");
		}
		echo("\t\t\t</ul>\n");
		echo("\t\t</div>\n");
		echo("\t\t<p id='miqubase'>MiQUBase</p>\n");
		echo("\t\t<p class='spacer'></p>\n");
		echo("\t</header>\n\n");
		echo("\t<div id='content'>\n");
	}//end createHeader

	/*
	 * Function createErrorPage creates the default MiQUBase error page.
	 * Parameter messageArray: array of strings; message(s) to be displayed on the error page
	 */
	function createErrorPage($messageArray)
	{
		$content = "<?php\n";
		$content .= "/* Copyright (C) 2017-2018 Véronique Wuyts\n";
		$content .= " * student at Thomas More Mechelen-Antwerpen vzw -- Campus De Nayer\n";
		$content .= " * Professionele Bachelor Elektronica-ICT\n";
		$content .= " *\n";
		$content .= " * MiQUBase is free software: you can redistribute it and/or modify\n";
		$content .= " * it under the terms of the GNU General Public License as published by\n";
		$content .= " * the Free Software Foundation, either version 3 of the License, or\n";
		$content .= " * (at your option) any later version.\n";
		$content .= " *\n";
		$content .= " * MiQUBase is distributed in the hope that it will be useful,\n";
		$content .= " * but WITHOUT ANY WARRANTY; without even the implied warranty of\n";
		$content .= " * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the\n";
		$content .= " * GNU General Public License for more details.\n";
		$content .= " *\n";
		$content .= " * You should have received a copy of the GNU General Public License\n";
		$content .= " * along with MiQUBase. If not, see <http://www.gnu.org/licenses/>.\n";
		$content .= " */\n";
		$content .= "require '../php/functions.php';\n";
		$content .= "\$script = \"\t\t\twindow.setTimeout(function(){window.location = '../php/home.php';},10000);\";\n";
		$content .= "createHead(true, 'MiQUBase error', ['message'], \$script);\n";
		$content .= "createHeader(null, false);\n";
		$content .= "?>\n";
		for ($i = 0; $i < count($messageArray); $i++)
		{
			$content .= "\t\t<p class='message'>". $messageArray[$i] ."</p>\n";
		}
		$content .= "\t\t<p class='message'>You will be redirected to the home page.</p>\n";
		$content .= "\t\t<p><a href='../php/home.php'>Back to home page</a></p>\n";
		$content .= "<?php\n";
		$content .= "createFooter(true);\n";
		$content .= "?>\n";
		file_put_contents('../message/error.php', $content);
		header("Location: ../message/error.php");
	}//end createErrorPage
	
	/*
	 * Function createMessagePage creates the default MiQUBase message page.
	 * Parameter messageArray: array of strings; message(s) to be displayed on the message page
	 *			 user: string; the user that is logged in
	 *			 php: string; the php page that is referenced in the link button
	 *			 button: string: the value of the link button
	 */
	function createMessagePage($messageArray, $user, $php, $button)
	{
		$content = "<?php\n";
		$content .= "/* Copyright (C) 2017-2018 Véronique Wuyts\n";
		$content .= " * student at Thomas More Mechelen-Antwerpen vzw -- Campus De Nayer\n";
		$content .= " * Professionele Bachelor Elektronica-ICT\n";
		$content .= " *\n";
		$content .= " * MiQUBase is free software: you can redistribute it and/or modify\n";
		$content .= " * it under the terms of the GNU General Public License as published by\n";
		$content .= " * the Free Software Foundation, either version 3 of the License, or\n";
		$content .= " * (at your option) any later version.\n";
		$content .= " *\n";
		$content .= " * MiQUBase is distributed in the hope that it will be useful,\n";
		$content .= " * but WITHOUT ANY WARRANTY; without even the implied warranty of\n";
		$content .= " * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the\n";
		$content .= " * GNU General Public License for more details.\n";
		$content .= " *\n";
		$content .= " * You should have received a copy of the GNU General Public License\n";
		$content .= " * along with MiQUBase. If not, see <http://www.gnu.org/licenses/>.\n";
		$content .= " */\n";
		$content .= "require '../php/functions.php';\n";
		$content .= "createHead(true, 'MiQUBase message', ['message'], null);\n";
		$content .= "createHeader('".$user."', false);\n";
		$content .= "?>\n";
		for ($i = 0; $i < count($messageArray); $i++)
		{
			$content .= "\t\t<p class='message'>". $messageArray[$i] ."</p>\n";
		}
		$content .= "\t\t<p><a href='".$php."'>".$button."</a></p>\n";
		$content .= "<?php\n";
		$content .= "createFooter(true);\n";
		$content .= "?>";
		file_put_contents('../message/message.php', $content);
		header("Location: ../message/message.php");
	}//end createMessagePage
	
	/*
	 * Function cleanInputText cleans user text input.
	 * Parameter $input: string; the user input to be cleaned as text
	 */
	function cleanInputText($input)
	{
		$input = trim($input);
		$input = filter_var($input, FILTER_SANITIZE_STRING);
		return $input;
	}//end cleanInputText

	/*
	 * Function cleanInputint cleans user integer input.
	 * Parameter $input: string; the user input to be cleaned as an integer
	 */
	function cleanInputInt($input)
	{
		$input = trim($input);
		$input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
		return $input;
	}//end cleanInputInt

	/*
	 * Function cleanInputDecimal cleans user decimal input.
	 * Parameter $input: string; the user input to be cleaned as a decimal
	 */
	function cleanInputDecimal($input)
	{
		$input = trim($input);
		$input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); // Still possible that two or more '.' are left
		return $input;
	}//end cleanInputDecimal
	
	/*
	 * Function checkReceptionDate checks if all reception dates have been set.
	 * Parameter $array: array of arrays with a key 'receptiondate'
	 */
	function checkReceptionDate($array)
	{
		if (is_null($array)) // count(NULL) emits a warning as of PHP 7.2
		{
			return false;
		}
		for ($i = 0; $i < count($array); $i++)
		{
			if (empty($array[$i]['receptiondate']))
			{
				return false;
			}
		}
		return true;
	}//end checkReceptionDate
	 
	/*
	 * Function getValidDate checks if the given input is a valid reception date.
	 * Parameter $input: string; the user input which has to be validated
	 */
	function getValidDate($input)
	{
		$inputArr = explode("/", $input);
		if (is_null($inputArr)) // count(NULL) emits a warning as of PHP 7.2
		{
			return false;
		}
		if (count($inputArr) < 3)
		{
			return false;
		}
		$day = $inputArr[0];
		$month = $inputArr[1];
		$year = $inputArr[2];
		if (!checkdate($month, $day, $year))
		{
			return false;
		}
		if ($year < 2016)
		{
			return false;
		}
		return $year ."-". $month ."-". $day;
	}//end getValidDate
	
	/*
	 * Function getEuroDate gives the date in the European format dd-mm-yyyy.
	 * Parameter $input: string; the date in format yyyy-mm-dd
	 */
	function getEuroDate($input)
	{
		return date("d/m/Y", strtotime($input));
	}//end getEuroDate
?>