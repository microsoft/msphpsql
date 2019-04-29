--TEST--
Call stored procedures with inputs of different datatypes to get outputs of various types
--DESCRIPTION--
Similar to pdo_output_decimal.phpt but this time intentionally test some error cases 
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function testInvalidSize($conn, $proc)
{
    global $inValue1, $inValue2, $outValue1;
    
    // Pass an invalid size for the output parameter
    try {
        $stmt = $conn->prepare("{CALL $proc (?, ?, ?)}");
        $stmt->bindValue(1, $inValue1);
        $stmt->bindValue(2, $inValue2);
        $stmt->bindParam(3, $outValue1, PDO::PARAM_STR, -1);
        $stmt->execute();
    } catch (PDOException $e) {
        $error = '*Invalid size for output string parameter 3.  Input/output string parameters must have an explicit length.';

        if (!fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
}

function testInvalidDirection($conn, $proc)
{
    global $inValue1, $inValue2, $outValue1;
    
    // Request input output parameter but do not provide a size
    try {
        $stmt = $conn->prepare("{CALL $proc (?, ?, ?)}");
        $stmt->bindValue(1, $inValue1);
        $stmt->bindValue(2, $inValue2);
        $stmt->bindParam(3, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
        $stmt->execute();
    } catch (PDOException $e) {
        $error = '*Invalid direction specified for parameter 3.  Input/output parameters must have a length.';

        if (!fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
}

function testInvalidType($conn, $proc)
{
    global $inValue1, $inValue2;
    
    $outValue = 0.3;
    
    // Pass an invalid type that is incompatible for the output parameter
    try {
        $stmt = $conn->prepare("{CALL $proc (?, ?, ?)}");
        $stmt->bindValue(1, $inValue1);
        $stmt->bindValue(2, $inValue2);
        $stmt->bindParam(3, $outValue, PDO::PARAM_BOOL | PDO::PARAM_INPUT_OUTPUT, 1024);
        $stmt->execute();
    } catch (PDOException $e) {
        $error = '*Types for parameter value and PDO::PARAM_* constant must be compatible for input/output parameter 3.';

        if (!fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
}

try {
    $conn = connect();

    $proc_scale = getProcName('scale_proc');
    $proc_no_scale = getProcName('noScale_proc');

    $stmt = $conn->exec("CREATE PROC $proc_scale (@p1 DECIMAL(18, 1), @p2 DECIMAL(18, 1), @p3 CHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(128), @p1 + @p2) END");

    $inValue1 = '2.1';
    $inValue2 = '5.3';
    $outValue1 = '0';
    $outValue2 = '0';

    // First error case: pass an invalid size for the output parameter
    testInvalidSize($conn, $proc_scale);
    testInvalidDirection($conn, $proc_scale);
    testInvalidType($conn, $proc_scale);

    $stmt = $conn->prepare("{CALL $proc_scale (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue1, PDO::PARAM_STR, 300);
    $stmt->execute();

    $outValue1 = trim($outValue1);

    $stmt = $conn->exec("CREATE PROC $proc_no_scale (@p1 DECIMAL, @p2 DECIMAL, @p3 CHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(128), @p1 + @p2) END");

    $stmt = $conn->prepare("{CALL $proc_no_scale (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue2, PDO::PARAM_STR, 300);
    $stmt->execute();

    $outValue2 = trim($outValue2);

    $expected1 = "7.4";
    $expected2 = "7";
    if ($outValue1 == $expected1 && $outValue2 == $expected2) {
        echo "Test Successfully done\n";
    }

    dropProc($conn, $proc_scale);
    dropProc($conn, $proc_no_scale);

    unset($stmt);
    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
Test Successfully done
