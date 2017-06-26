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
include 'MsCommon.inc';

function StoredProc()
{
    include 'MsSetup.inc';

    $testName = "Stored Proc - Null Data";
    $data1 = "Microsoft SQL Server ";
    $data2 = "Driver for PHP";

    StartTest($testName);

    Setup();
    $conn1 = Connect();

    ExecProc($conn1, $procName, "VARCHAR(32)", SQLSRV_SQLTYPE_VARCHAR(32), "ABC");
    ExecProc($conn1, $procName, "FLOAT", SQLSRV_SQLTYPE_FLOAT, 3.2);
    ExecProc($conn1, $procName, "INT", SQLSRV_SQLTYPE_INT, 5);

    sqlsrv_close($conn1);

    EndTest($testName);
}

function ExecProc($conn, $procName, $sqlType, $sqlTypeEx, $initData)
{
    $data = $initData;

    $procArgs = "@p1 $sqlType OUTPUT";
    $procCode = "SET @p1 = NULL";
    $callArgs =  array(array(&$data, SQLSRV_PARAM_OUT, null, $sqlTypeEx));


    CreateProc($conn, $procName, $procArgs, $procCode);
    CallProc($conn, $procName, "?", $callArgs);
    DropProc($conn, $procName);

    if ($data != null)
    {
        die("Data corruption: [$data] instead of null.");
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        StoredProc();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Stored Proc - Null Data" completed successfully.
