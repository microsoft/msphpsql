<?php
	// Set SQL server + user + password
	$serverName = getenv('MSSQL_SERVERNAME') ?: "localhost";//"sql-2k16-04.galaxy.ad";
	$username   = getenv('MSSQL_USERNAME') ?:   "sa";
	$password   = getenv('MSSQL_PASSWORD') ?:   "yourStrong!Passw0rd";//"Moonshine4me";


	// Generate unique DB name, example: php_20160817_1471475608267
	$dbName = "php_" . date("Ymd") . "_" . round(microtime(true)*1000);

	// Generic table name example: php_20160817_1471475608267.dbo.php_firefly
	$tableName = $dbName.".dbo.php_firefly";
	
	// Connection options
	$connectionInfo = array("UID"=>"$username", "PWD"=>"$password");
?>
