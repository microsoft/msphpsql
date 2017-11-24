--TEST--
call stored procedures with inputs of ten different datatypes to get outputs of various types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

include_once("MsCommon_mid-refactor.inc");

function procFetchBigInt($conn)
{
    $procName = getProcName('bigint');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 BIGINT, @p2 BIGINT, @p3 NCHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(NCHAR(128), @p1 + @p2) END");

    $inValue1 = '12345678';
    $inValue2 = '11111111';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "23456789";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchDecimal($conn)
{
    $procName = getProcName('decimal');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 DECIMAL, @p2 DECIMAL, @p3 CHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(128), @p1 + @p2) END");

    $inValue1 = '2.1';
    $inValue2 = '5.3';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "7";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchFloat($conn)
{
    $procName = getProcName('float');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 FLOAT, @p2 FLOAT, @p3 FLOAT OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(FLOAT, @p1 + @p2) END");

    $inValue1 = '2.25';
    $inValue2 = '5.5';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "7.75";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchInt($conn)
{
    $procName = getProcName('int');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 INT, @p2 INT, @p3 INT OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(INT, @p1 + @p2) END");

    $inValue1 = '1234';
    $inValue2 = '5678';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "6912";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchMoney($conn)
{
    $procName = getProcName('money');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 MONEY, @p2 MONEY, @p3 MONEY OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(MONEY, @p1 + @p2) END");

    $inValue1 = '22.3';
    $inValue2 = '16.1';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1, PDO::PARAM_STR);
    $stmt->bindParam(2, $inValue2, PDO::PARAM_STR);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "38.40";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchNumeric($conn)
{
    $procName = getProcName('numeric');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 NUMERIC, @p2 NUMERIC, @p3 NCHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(NCHAR(128), @p1 + @p2) END");

    $inValue1 = '2.8';
    $inValue2 = '5.4';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "8";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchReal($conn)
{
    $procName = getProcName('real');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 REAL, @p2 REAL, @p3 REAL OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(REAL, @p1 + @p2) END");

    $inValue1 = '3.4';
    $inValue2 = '6.6';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "10";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function procFetchSmallInt($conn)
{
    $procName = getProcName('smallint');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 SMALLINT, @p2 SMALLINT, @p3 NCHAR(32) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(NCHAR(32), @p1 + @p2) END");

    $inValue1 = '34';
    $inValue2 = '56';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "90";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($conn);
}

function procFetchSmallMoney($conn)
{
    $procName = getProcName('smallmoney');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 SMALLMONEY, @p2 SMALLMONEY, @p3 SMALLMONEY OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(SMALLMONEY, @p1 + @p2) END");

    $inValue1 = '10';
    $inValue2 = '11.7';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1, PDO::PARAM_STR);
    $stmt->bindParam(2, $inValue2, PDO::PARAM_STR);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "21.70";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

function ProcFetch_TinyInt($conn)
{
    $procName = getProcName('tinyint');

    $stmt = $conn->exec("CREATE PROC $procName (@p1 TINYINT, @p2 TINYINT, @p3 CHAR(32) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(32), @p1 + @p2) END");

    $inValue1 = '11';
    $inValue2 = '12';
    $outValue = '0';

    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $expected = "23";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }

    dropProc($conn, $procName);
    unset($stmt);
}

set_time_limit(0);
echo "Starting test...\n";
try {
    $conn = connect();

    procFetchBigInt($conn);
    procFetchDecimal($conn);
    procFetchFloat($conn);
    procFetchInt($conn);
    procFetchMoney($conn);
    procFetchNumeric($conn);
    procFetchReal($conn);
    procFetchSmallInt($conn);
    procFetchSmallMoney($conn);
    ProcFetch_TinyInt($conn);
    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "Done\n";
?>
--EXPECT--
Starting test...
Done
