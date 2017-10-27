--TEST--
Stored Proc Null Data Test
--DESCRIPTION--
Verifies the ability to return a null string from a stored procedure.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function storedProc()
{
    $testName = "Stored Proc - Null Data";
    $data1 = "Microsoft SQL Server ";
    $data2 = "Driver for PHP";

    startTest($testName);
    setup();
    $tableName = 'TC76test';
    $procName = "TC76test_proc";
    $conn1 = AE\connect();

    execProc($conn1, $procName, "VARCHAR(32)", SQLSRV_SQLTYPE_VARCHAR(32), "ABC");
    execProc($conn1, $procName, "FLOAT", SQLSRV_SQLTYPE_FLOAT, 3.2);
    execProc($conn1, $procName, "INT", SQLSRV_SQLTYPE_INT, 5);

    sqlsrv_close($conn1);

    endTest($testName);
}

function execProc($conn, $procName, $sqlType, $sqlTypeEx, $initData)
{
    $data = $initData;

    $procArgs = "@p1 $sqlType OUTPUT";
    $procCode = "SET @p1 = NULL";
    $callArgs =  array(array(&$data, SQLSRV_PARAM_OUT, null, $sqlTypeEx));

    createProc($conn, $procName, $procArgs, $procCode);
    callProc($conn, $procName, "?", $callArgs);
    dropProc($conn, $procName);

    if ($data != null) {
        die("Data corruption: [$data] instead of null.");
    }
}

try {
    storedProc();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Stored Proc - Null Data" completed successfully.
