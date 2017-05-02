<?php
/*
    Microsoft SQL Server Driver for PHP - Unit Test Framework
    Copyright (c) Microsoft Corporation.  All rights reserved.

    Description:
        Global variables defining the execution context

*/
$PhpDriver = "Microsoft SQL Server Driver for PHP";

$server = 'SQL-2K12-SP3-1.galaxy.ad';
$database = 'test_win123';
$userName = 'sa';
$userPassword = 'Moonshine4me';

$tableName = "php_test_table";
$tableIndex = "php_test_table_index";
$procName = "php_test_proc";
$fileName = "php_test_file.dat";

$connectionOptions = array("Database"=>$database, "UID"=>$userName, "PWD"=>$userPassword, "TraceOn"=>false);
$daasMode = false;
$marsMode = true;

$traceEnabled = false; 

if (isset($_ENV['MSSQL_SERVER']) || isset($_ENV['MSSQL_USER']) || isset($_ENV['MSSQL_PASSWORD'])) {
    $server = $_ENV['MSSQL_SERVER'];
    $uid = $_ENV['MSSQL_USER'];
    $pwd = $_ENV['MSSQL_PASSWORD'];
    $databaseName = $_ENV['MSSQL_DATABASE_NAME'];
    $DriverName = $_ENV['MSSQL_DRIVER_NAME'];
} else {
    $uid = $userName; 
    $pwd = $userPassword; 
    $databaseName = $database; 
    $DriverName = "ODBC Driver 11 for SQL Server";
}
?>
