--TEST--
Stored Proc Query Test
--DESCRIPTION--
Verifies the data retrieved via a store procedure to validate behavior
of queries including SQLSRV_PARAM_OUT qualifiers.
Checks all numeric data types (i.e. 10 SQL types).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function procQuery($minType, $maxType)
{
    $testName = "Stored Proc Query";
    startTest($testName);

    setup();
    $tableName = 'TC74test';
    $procName = "TC74test_proc";
    $conn1 = AE\connect();

    for ($k = $minType; $k <= $maxType; $k++) {
        switch ($k) {
        case 1: // TINYINT
            execProcQuery($conn1, $procName, $k, "TINYINT", 11, 12, 23);
            break;

        case 2: // SMALLINT
            execProcQuery($conn1, $procName, $k, "SMALLINT", 4.3, 5.5, 9);
            break;

        case 3: // INT
            execProcQuery($conn1, $procName, $k, "INT", 3.2, 4, 7);
            break;

        case 4: // BIGINT
            execProcQuery($conn1, $procName, $k, "BIGINT", 5.2, 3.7, 8);
            break;

        case 5: // FLOAT
            execProcQuery($conn1, $procName, $k, "FLOAT", 2.5, 5.25, 7.75);
            break;

        case 6: // REAL
            execProcQuery($conn1, $procName, $k, "REAL", 3.4, 6.6, 10);
            break;

        case 7: // DECIMAL
            execProcQuery($conn1, $procName, $k, "DECIMAL", 2.1, 5.3, 7);
            break;

        case 8: // NUMERIC
            execProcQuery($conn1, $procName, $k, "NUMERIC", 2.8, 5.4, 8);
            break;

        case 9: // SMALLMONEY
            execProcQuery($conn1, $procName, $k, "SMALLMONEY", 10, 11.7, 21.7);
            break;

        case 10:// MONEY
            execProcQuery($conn1, $procName, $k, "MONEY", 22.3, 16.1, 38.4);
            break;

        default:// default
            break;
        }
    }

    sqlsrv_close($conn1);

    endTest($testName);
}

function execProcQuery($conn, $procName, $type, $dataType, $inData1, $inData2, $outData)
{
    $procArgs = "@p1 $dataType, @p2 $dataType, @p3 $dataType OUTPUT";
    $procCode = "SELECT @p3 = CONVERT($dataType, @p1 + @p2)";
    createProc($conn, $procName, $procArgs, $procCode);

    $callArgs = "?, ?, ?";
    $callResult = 0.0;
    if (!AE\isColEncrypted()) {
        $callValues = array($inData1, $inData2, array(&$callResult, SQLSRV_PARAM_OUT));
    } else {
        if ($type == 7 || $type == 8) { 
            // DECIMAL or NUMERIC
            $driverType = call_user_func("SQLSRV_SQLTYPE_$dataType", 2, 1);
        } else {
            $driverType = constant("SQLSRV_SQLTYPE_$dataType");
        }
        
        if ($type >= 1 && $type < 5) { 
            // for any kinds of integers convert the inputs to integers first
            // AE is stricter with data types
            $inData1 = floor($inData1);
            $inData2 = floor($inData2);
        }
        $callValues = array(array($inData1, null, null, $driverType),
                        array($inData2, null, null, $driverType),
                        array(&$callResult, SQLSRV_PARAM_OUT, null, $driverType));
    }
    
    callProc($conn, $procName, $callArgs, $callValues);
    dropProc($conn, $procName);

    traceData($dataType, "".$inData1." + ".$inData2." = ".$callResult);
    if ($callResult != $outData) {
        die("Expected result for ".$dataType." was ".$outData);
    }
}

try {
    procQuery(1, 10);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Stored Proc Query" completed successfully.
