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
    $server = 'TARGET_SERVER';
    $uid = 'TARGET_USERNAME';
    $pwd = 'TARGET_PASSWORD';
    $databaseName = 'TARGET_DATABASE';
    $DriverName = "ODBC Driver 11 for SQL Server";
}

$adServer = 'TARGET_AD_SERVER';
$adDatabase = 'TARGET_AD_DATABASE';
$adUser = 'TARGET_AD_USERNAME';
$adPassword = 'TARGET_AD_PASSWORD';

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

// column encryption variables
$keystore = "none";         // key store provider, acceptable values are none, win, ksp, akv
$dataEncrypted = false;     // whether data is to be encrypted

?>