<?php
/*
    Microsoft SQL Server Driver for PHP - Unit Test Framework
    Copyright (c) Microsoft Corporation.  All rights reserved.

    Description:
        Common functions (shared by all tests).

*/

require_once('MsHelper.inc');
require_once('MsSetup.inc');

$usingUTF8data = false;

function isWindows()
{
    // This method returns TRUE when running in a Windows platform
    // The possible values are WIN32, WINNT and Windows
    return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
}

function useUTF8Data()
{
    global $usingUTF8data;
    return $usingUTF8data;
}

function setUTF8Data($val)
{
    global $usingUTF8data;
    $usingUTF8data = $val;
}

function testMode()
{
    $testMode = getenv('PHPT_EXEC');
    return ($testMode ? true : false);
}

function traceMode()
{
    global $traceEnabled;
    return ((!testMode() && $traceEnabled) ? true : false);
}

function trace($msg)
{
    if (traceMode()) {
        echo $msg;
    }
}

/**
 * This method prints the message when traceMode() is true
 *
 * @param  string  $msg
 * @return void
 */
function traceData($sqlType, $data)
{
    if (traceMode()) {
        $msg = strtoupper(" $sqlType:");
        echo "$msg\t";
        if (strlen($msg) <= 7) {
            echo "\t";
        }
        if (strlen($msg) <= 15) {
            echo "\t";
        }
        echo "$data\n";
    }
}

function isMarsSupported()
{
    global $marsMode;
    return ($marsMode ? true : false);
}

function isDaasMode()
{
    global $daasMode;
    return ($daasMode ? true : false);
}

function startTest($testName)
{
    if (traceMode()) {
        echo "Starting \"$testName\" test...\n\n";
    }
}

/**
 * This method signals the end of a test given its name
 *
 * @return void
 */
function endTest($testName)
{
    echo "Test \"$testName\" completed successfully.\n";
}

function setup()
{
    set_time_limit(0);
    sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);
    sqlsrv_configure('WarningsReturnAsErrors', 1);
}

function configure($param, $expected)
{
    sqlsrv_configure($param, $expected);
    $actual = sqlsrv_get_config($param);

    if ($actual == $expected) {
        trace("Set configuration parameter $param = $actual.\n");
    } else {
        die("Failed to set configuration parameter $param = $expected.");
    }
}

function connect($options = array())
{
    include('MsSetup.inc');

    if (!empty($options)) {
        $connectionOptions = array_merge($connectionOptions, $options);
    }

    trace("Attempting connection to $server...");
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        fatalError("Failed to connect to $server.");
    }
    trace(" successfully connected.\n\n");
    return ($conn);
}

function getTempTableName($table = '', $temporary = true)
{
    // A temporary table name with the '#' prefix will be automatically
    // dropped once the connection is closed. Otherwise, the caller
    // should take care of dropping the temp table afterwards.

    $someNumber = rand(0, 1000);

    $prefix = '';
    if ($temporary) {
        $prefix = '#';
    }

    if (strlen($table) == 0) {
        $table = 'php_test_table';
    }

    return $prefix . $table . '_' . $someNumber;
}

function getTempProcName($proc = '', $temporary = true)
{
    // A temporary stored procedure name with the '#' prefix will be
    // automatically dropped once the connection is closed. Otherwise,
    // the caller should take care of dropping the temp procedure afterwards.

    $someNumber = rand(0, 1000);

    $prefix = '';
    if ($temporary) {
        $prefix = '#';
    }

    if (strlen($proc) == 0) {
        $proc = 'php_test_proc';
    }

    return $prefix . $proc . '_' . $someNumber;
}

function executeQuery($conn, $query)
{
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false) {
        fatalError("Query execution failed: $query");
    }
    return ($stmt);
}


function prepareQuery($conn, $query)
{
    $stmt = sqlsrv_prepare($conn, $query);
    if ($stmt === false) {
        fatalError("Query preparation failed: $query");
    }
    return ($stmt);
}


function executeQueryEx($conn, $query, $modeDirect)
{
    if ($modeDirect) {   // direct execution
        $stmt = sqlsrv_query($conn, $query);
    } else {
        $stmt = prepareQuery($conn, $query);
        sqlsrv_execute($stmt);
    }
    return ($stmt);
}

function createTableEx($conn, $tableName, $dataType)
{
    $sql = "CREATE TABLE [$tableName] ($dataType)";
    dropTable($conn, $tableName);
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        fatalError("Failed to create test table: ".$sql);
    }
    sqlsrv_free_stmt($stmt);
}


function createTableIndex($conn, $tableName, $colIndex)
{
    include 'MsSetup.inc';
    createTableIndexEx($conn, $tableName, $tableIndex, $colIndex);
}


function createTableIndexEx($conn, $tableName, $tableIndex, $colIndex)
{
    trace("Creating table index for $tableName ...");
    $sqlIndex = "CREATE CLUSTERED INDEX [$tableIndex] ON [$tableName]($colIndex)";
    $stmt = sqlsrv_query($conn, $sqlIndex);
    if ($stmt === false) {
        fatalError("Failed to create clustered index for test table: ".$sqlIndex);
    }
    sqlsrv_free_stmt($stmt);
    trace(" completed successfully.\n");
}

function createUniqueIndex($conn, $tableName, $colIndex)
{
    include 'MsSetup.inc';
    createUniqueIndexEx($conn, $tableName, $tableIndex, $colIndex);
}

function createUniqueIndexEx($conn, $tableName, $tableIndex, $colIndex)
{
    trace("Creating unique table index for $tableName ...");
    $sqlIndex = "CREATE UNIQUE INDEX [$tableIndex] ON [$tableName]($colIndex)";
    $stmt = sqlsrv_query($conn, $sqlIndex);
    if ($stmt === false) {
        fatalError("Failed to create unique index for test table: ".$sqlIndex);
    }
    sqlsrv_free_stmt($stmt);
    trace(" completed successfully.\n");
}

function dropTable($conn, $tableName)
{
    $stmt = sqlsrv_query($conn, "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE [$tableName]");
    if ($stmt === false) {
    } else {
        sqlsrv_free_stmt($stmt);
    }
}

function selectFromTable($conn, $tableName)
{
    return (selectFromTableEx($conn, $tableName, null));
}

function selectFromTableEx($conn, $tableName, $cond)
{
    if (($cond != null) && (strlen($cond) > 0)) {
        return (selectQuery($conn, "SELECT * FROM [$tableName] WHERE $cond"));
    } else {
        return (selectQuery($conn, "SELECT * FROM [$tableName]"));
    }
}

function selectQuery($conn, $query)
{
    return (selectQueryEx($conn, $query, null));
}

function selectQueryEx($conn, $query, $options)
{
    if ($options != null) {
        $stmt = sqlsrv_query($conn, $query, null, $options);
    } else {
        $stmt = sqlsrv_query($conn, $query);
    }
    if ($stmt === false) {
        fatalError("Failed to query test table");
    }

    $numFields = sqlsrv_num_fields($stmt);
    if ($numFields <= 0) {
        die("Unexpected number of fields: ".$numFields);
    }
    return ($stmt);
}

function rowCount($stmt)
{
    $rowCount = 0;
    while (sqlsrv_fetch($stmt)) {
        $rowCount++;
    }
    return ($rowCount);
}


function numRows($conn, $tableName)
{
    $stmt = SelectFromTable($conn, $tableName);
    $rowCount = rowCount($stmt);
    sqlsrv_free_stmt($stmt);

    return ($rowCount);
}

function insertCheck($stmt)
{
    if ($stmt === false) {
        fatalError("Failed to insert row into test table");
    }
    $numRows = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);
    if ($numRows != 1) {
        die("Unexpected row count at insert: ".$numRows);
    }
    return (true);
}

function createProc($conn, $procName, $procArgs, $procCode)
{
    dropProc($conn, $procName);
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($procArgs) AS BEGIN $procCode END");
    if ($stmt === false) {
        fatalError("Failed to create test procedure");
    }
    sqlsrv_free_stmt($stmt);
}

function dropProc($conn, $procName)
{
    $stmt = sqlsrv_query($conn, "IF OBJECT_ID('". $procName ."', 'P') IS NOT NULL DROP PROCEDURE [$procName]");
    if ($stmt === false) {
    } else {
        sqlsrv_free_stmt($stmt);
    }
}

function callProc($conn, $procName, $procArgs, $procValues)
{
    $stmt = callProcEx($conn, $procName, "", $procArgs, $procValues);
    sqlsrv_free_stmt($stmt);
}

function callProcEx($conn, $procName, $procPrefix, $procArgs, $procValues)
{
    $stmt = sqlsrv_query($conn, "{ $procPrefix CALL [$procName] ($procArgs)}", $procValues);
    if ($stmt === false) {
        fatalError("Failed to call test procedure");
    }
    return ($stmt);
}

function createFunc($conn, $funcName, $funcArgs, $retType, $funcCode)
{
    dropFunc($conn, $funcName);
    $stmt = sqlsrv_query($conn, "CREATE FUNCTION [$funcName] ($funcArgs) RETURNS $retType AS BEGIN $funcCode END");
    if ($stmt === false) {
        fatalError("Failed to create test function");
    }
    sqlsrv_free_stmt($stmt);
}

function dropFunc($conn, $funcName)
{
    $stmt = sqlsrv_query($conn, "DROP FUNCTION [$funcName]");
    if ($stmt === false) {
    } else {
        sqlsrv_free_stmt($stmt);
    }
}

function callFunc($conn, $funcName, $funcArgs, $funcValues)
{
    $stmt = sqlsrv_query($conn, "{ ? = CALL [$funcName]($funcArgs)}", $funcValues);
    if ($stmt === false) {
        fatalError("Failed to call test function");
    }
    sqlsrv_free_stmt($stmt);
}

function fatalError($errorMsg, $print = true) 
{
    SetUTF8Data(false);
    if ($print) {
        printErrors();
    } else {
        handleErrors();
    }
    die($errorMsg."\n");
}

function printErrors($message = "")
{
    if (strlen($message) > 0) {
        echo $message . "\n";
    }
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    $count = 0;
    if (!empty($errors)) {
        $count = count($errors);
    } else {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
        if (!empty($errors)) {
            $count = count($errors);
        }
    }

    for ($i = 0; $i < $count; $i++) {
        echo $errors[$i]['message'] . "\n";
    }
}

function handleErrors()
{
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    $count = 0;
    if (!empty($errors)) {
        $count = count($errors);
    } else {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
        if (!empty($errors)) {
            $count = count($errors);
        }
    }

    for ($i = 0; $i < $count; $i++) {
        trace($errors[$i]['message']."\n");
    }
}

function setUSAnsiLocale()
{
    if (!isWindows()) {
        // macOS the locale names are different in Linux or macOS
        $locale = strtoupper(PHP_OS) === 'LINUX' ? "en_US.ISO-8859-1" : "en_US.ISO8859-1";
        
        setlocale(LC_ALL, $locale);
    }
}

function resetLocaleToDefault()
{
    // Like setUSAnsiLocale() above, this method is only needed in non-Windows environment
    if (!isWindows()) {
        setlocale(LC_ALL, null);
    }
}

// non-UTF8 locale support in ODBC 17 and above only
// if AE enabled, only supported in Windows (AE limitations)
function isLocaleSupported()
{
    if (isWindows()) {
        return true;
    }
    if (AE\isDataEncrypted()) {
        return false;
    }
    // now check ODBC version
    $conn = AE\connect();
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    if (explode(".", $msodbcsql_ver)[0] < 17) {
        return false;
    }

    return true;
}

function verifyError($error, $state, $message)
{
    if ($error['SQLSTATE'] !== $state) {
        echo $error['SQLSTATE'] . PHP_EOL;
        fatalError("Unexpected SQL state\n");
    }
    
    if (strpos($error['message'], $message) === false) {
        echo $error['message'] . PHP_EOL;
        fatalError("Unexpected error message\n");
    }
}

?>
