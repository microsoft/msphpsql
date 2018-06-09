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
}

$adServer = 'TARGET_AD_SERVER';
$adDatabase = 'TARGET_AD_DATABASE';
$adUser = 'TARGET_AD_USERNAME';
$adPassword = 'TARGET_AD_PASSWORD';

$driverType = true;
$driver = "ODBC Driver 17 for SQL Server";

$tableName = 'pdo_test_table';
$tableIndex = 'php_test_table_idx';
$procName = 'php_test_proc';
$fileName = 'php_test_file.dat';
$connectionOptions = array();
$daasMode = false;
$marsMode = true;
$dsnMode = true;
$traceEnabled = false;

// column encryption variables
$keystore = "none";         // key store provider, acceptable values are none, win, ksp, akv
$dataEncrypted = false;     // whether data is to be encrypted

// for Azure Key Vault
$AKVKeyStoreAuthentication = 'TARGET_AKV_AUTH';   // can be KeyVaultPassword or KeyVaultClientSecret
$AKVPrincipalName = 'TARGET_AKV_PRINCIPAL_NAME';  // for use with KeyVaultPassword
$AKVPassword = 'TARGET_AKV_PASSWORD';             // for use with KeyVaultPassword
$AKVClientID = 'TARGET_AKV_CLIENT_ID';            // for use with KeyVaultClientSecret
$AKVSecret = 'TARGET_AKV_CLIENT_SECRET';          // for use with KeyVaultClientSecret

?>