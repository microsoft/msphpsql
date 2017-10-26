--TEST--
Stored Proc Null Data Test
--DESCRIPTION--
Verifies the ability to return a null string from a stored procedure.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function StoredProc()
{
    include 'MsSetup.inc';

    $testName = "Stored Proc - Null Data";
    $data1 = "Microsoft SQL Server ";
    $data2 = "Driver for PHP";

    startTest($testName);

    setup();
    $conn1 = connect();

    ExecProc($conn1, $procName, "VARCHAR(32)", SQLSRV_SQLTYPE_VARCHAR(32), "ABC");
    ExecProc($conn1, $procName, "FLOAT", SQLSRV_SQLTYPE_FLOAT, 3.2);
    ExecProc($conn1, $procName, "INT", SQLSRV_SQLTYPE_INT, 5);

    sqlsrv_close($conn1);

    endTest($testName);
}

function ExecProc($conn, $procName, $sqlType, $sqlTypeEx, $initData)
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

//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        StoredProc();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test "Stored Proc - Null Data" completed successfully.
