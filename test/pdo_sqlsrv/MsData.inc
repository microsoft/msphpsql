<?php
/*
	Microsoft SQL Server Driver for PHP - Unit Test Framework
	Copyright (c) Microsoft Corporation.  All rights reserved.

	Description:
		Random data generator for INSERT statements.

*/

// globals

$table1 = "PDO_Types_1";
$table2=  "PDO_AllTypes";

$large_string  = "This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.";

$string_col = array("'STRINGCOL1'", "'STRINGCOL2'");

$date_col = array("'2000-11-11 11:11:11.111'", "'2000-11-11 11:11:11.222'");

$large_string_col = array("' 1 " . $large_string . "'", "' 2 " . $large_string . "'");
 
$xml_col = array("'<xml> 1 " . $large_string . "</xml>'", "'<xml> 2 " . $large_string . "</xml>'");

$binary_col = array('0x0', '0x0');

$int_col = array ( 1, 2);

$decimal_col = array (111.111, 222.222);

$guid_col = array("'AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA'", "'BBBBBBBB-BBBB-BBBB-BBBB-BBBBBBBBBBBB'");

$null_col = 'null';

$comma = ",";
$closing_brace = "),";

class PDO_Types_1_Class{
	var $IntCol;
	var $CharCol;
	var $NCharCol;
	var $DateTimeCol;
	var $VarcharCol;
	var $NVarCharCol;
	var $FloatCol;
	var $XmlCol;
	
	function dumpAll()
	{
		var_dump($this->IntCol);
		var_dump($this->CharCol);
		var_dump($this->NCharCol);
		var_dump($this->DateTimeCol);
		var_dump($this->VarcharCol);
		var_dump($this->NVarCharCol);
		var_dump($this->FloatCol);
		var_dump($this->XmlCol);		
	}	
}
$table1_class = 'PDO_Types_1_Class';



?>
