--TEST--
Stored Function Test
--DESCRIPTION--
Verifies the ability to create and subsequently call a stored procedure.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function storedFunc()
{
    $testName = "Stored Function";
    $data1 = "Microsoft SQL Server ";
    $data2 = "Driver for PHP";
    $tableName = 'TC72test';
    $procName = "TC72test_proc";

    startTest($testName);

    setup();
    $conn1 = AE\connect();

    execFunc($conn1, $procName, "CHAR", $data1, $data2);
    execFunc($conn1, $procName, "VARCHAR", $data1, $data2);
    execFunc($conn1, $procName, "NCHAR", $data1, $data2);
    execFunc($conn1, $procName, "NVARCHAR", $data1, $data2);

    sqlsrv_close($conn1);

    endTest($testName);
}

function execFunc($conn, $funcName, $sqlType, $inValue1, $inValue2)
{
    $len1 = strlen($inValue1);
    $len2 = strlen($inValue2);
    $len = $len1 + $len2;
    $sqlTypeIn1 = "$sqlType($len1)";
    $sqlTypeIn2 = "$sqlType($len2)";
    $sqlTypeOut = "$sqlType($len)";
    $expected = "$inValue1$inValue2";
    $actual = "";

    // Always Encrypted feature requires SQL Types to be specified for sqlsrv_query
    // https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation
    if (AE\isColEncrypted()) {
        $driverTypeIn1 = call_user_func("SQLSRV_SQLTYPE_$sqlType", $len1);
        $driverTypeIn2 = call_user_func("SQLSRV_SQLTYPE_$sqlType", $len2);
    } else {
        $driverTypeIn1 = null;
        $driverTypeIn2 = null;
    }

    $funcArgs = "@p1 $sqlTypeIn1, @p2 $sqlTypeIn2";
    $funcCode = "RETURN (CONVERT($sqlTypeOut, @p1 + @p2))";
    $callArgs =  array(array(&$actual, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_CHAR($len)),
               array($inValue1, SQLSRV_PARAM_IN, null, $driverTypeIn1),
               array($inValue2, SQLSRV_PARAM_IN, null, $driverTypeIn2));

    createFunc($conn, $funcName, $funcArgs, $sqlTypeOut, $funcCode);
    callFunc($conn, $funcName, "?, ?", $callArgs);
    dropFunc($conn, $funcName);

    if (strncmp($actual, $expected, strlen($expected)) != 0) {
        die("Data corruption: $expected => $actual.");
    }
}

try {
    storedFunc();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Stored Function" completed successfully.
