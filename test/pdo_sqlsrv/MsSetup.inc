<?php
/*
    Microsoft SQL Server Driver for PHP/PDO - Unit Test Framework
    Copyright (c) Microsoft Corporation.  All rights reserved

    Description:
        Global variables defining the execution context
*/

if (isset($_ENV['MSSQL_SERVER']) || isset($_ENV['MSSQL_USER']) || isset($_ENV['MSSQL_PASSWORD'])) {
    $server = $_ENV['MSSQL_SERVER'];
    $uid = $_ENV['MSSQL_USER'];
    $pwd = $_ENV['MSSQL_PASSWORD'];
    $databaseName = $_ENV['MSSQL_DATABASE_NAME'];
    $DriverName = $_ENV['MSSQL_DRIVER_NAME'];
} else {
    $server = 'SQL-2K12-SP3-1.galaxy.ad';
    $uid = 'sa';
    $pwd = 'Moonshine4me';
    $databaseName = 'test_win123';
    $DriverName = "ODBC Driver 11 for SQL Server";
}

$driverType = true;
$PhpDriver = "ODBC Driver 11 for SQL Server";

$tableName = 'pdo_test_table';
$tableIndex = 'php_test_table_idx';
$procName = 'php_test_proc';
$fileName = 'php_test_file.dat';
$dsn = "odbc:Driver={$DriverName};Server=$server";
$connectionOptions = array();
$daasMode = false;
$marsMode = true;
$dsnMode = true;
$traceEnabled = false;

?>
