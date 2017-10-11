--TEST--
Stored Proc Call Test
--DESCRIPTION--
Verifies the ability to create and subsequently call a stored procedure.
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

    $testName = "Stored Proc Call";
    $data1 = "Microsoft SQL Server ";
    $data2 = "Driver for PHP";

    startTest($testName);

    setup();
    $conn1 = connect();

    ExecProc($conn1, $procName, "CHAR", $data1, $data2);
    ExecProc($conn1, $procName, "VARCHAR", $data1, $data2);
    ExecProc($conn1, $procName, "NCHAR", $data1, $data2);
    ExecProc($conn1, $procName, "NVARCHAR", $data1, $data2);

    sqlsrv_close($conn1);

    endTest($testName);
}

function ExecProc($conn, $procName, $sqlType, $inValue1, $inValue2)
{
    $len1 = strlen($inValue1);
    $len2 = strlen($inValue2);
    $len = $len1 + $len2;
    $sqlTypeIn1 = "$sqlType($len1)";
    $sqlTypeIn2 = "$sqlType($len2)";
    $sqlTypeOut = "$sqlType($len)";
    $expected = "$inValue1$inValue2";
    $actual = "";

    $procArgs = "@p1 $sqlTypeOut OUTPUT, @p2 $sqlTypeIn1, @p3 $sqlTypeIn2";
    $procCode = "SET @p1 = CONVERT($sqlTypeOut, @p2 + @p3)";
    $callArgs =  array(array(&$actual, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_CHAR($len)),
               array($inValue1, SQLSRV_PARAM_IN),
               array($inValue2, SQLSRV_PARAM_IN));


    createProc($conn, $procName, $procArgs, $procCode);
    callProc($conn, $procName, "?, ?, ?", $callArgs);
    dropProc($conn, $procName);

    if (strncmp($actual, $expected, strlen($expected)) != 0) {
        die("Data corruption: $expected => $actual.");
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
Test "Stored Proc Call" completed successfully.
