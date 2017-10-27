--TEST--
Stored Proc Call Test
--DESCRIPTION--
Verifies the ability to create and subsequently call a stored procedure.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function storedProc()
{
    $testName = "Stored Proc Call";
    startTest($testName);

    setup();
    $tableName = 'TC73test';
    $procName = "TC73test_proc";
    $conn1 = AE\connect();

    $step = 0;
    $dataStr = "The quick brown fox jumps over the lazy dog.";
    $dataInt = 0;

    // Scenario #1: using a null buffer
    $step++;
    if (!execProc1($conn1, $procName, $dataStr, 40, 0)) {
        die("Execution failure at step $step.");
    }

    // Scenario #2: using a pre-allocated buffer
    $step++;
    if (!execProc1($conn1, $procName, $dataStr, 25, 1)) {
        die("Execution failure at step $step.");
    }

    // Scenario #3: specifying an exact return size
    $step++;
    if (!execProc1($conn1, $procName, $dataStr, 0, 2)) {
        die("Execution failure at step $step.");
    }

    // Scenario #4: specifying a larger return size
    $step++;
    if (!execProc1($conn1, $procName, $dataStr, 50, 2)) {
        die("Execution failure at step $step.");
    }

    // Scenario #5: returning a value
    $step++;
    if (!execProc2($conn1, $procName, $dataInt)) {
        die("Execution failure at step $step.");
    }

    sqlsrv_close($conn1);

    endTest($testName);
}

function execProc1($conn, $procName, $dataIn, $extraSize, $phpInit)
{
    $inValue = trim($dataIn);
    $outValue = null;
    $lenData = strlen($inValue);
    $len = $lenData + $extraSize;
    $procArgs = "@p1 VARCHAR($len) OUTPUT";
    $procCode = "SET @p1 = '$inValue'";

    if ($phpInit == 1) {
        $outValue = "";
        for ($i = 0; $i < $len; $i++) {   // fill the buffer with "A"
            $outValue = $outValue."A";
        }
    }
    $callArgs =  array(array(&$outValue, SQLSRV_PARAM_OUT,
                 SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR),
                         SQLSRV_SQLTYPE_VARCHAR($lenData + 1)));

    createProc($conn, $procName, $procArgs, $procCode);
    callProc($conn, $procName, "?", $callArgs);
    dropProc($conn, $procName);

    if ($inValue != trim($outValue)) {
        trace("Data corruption: [$inValue] => [$outValue]\n");
        return (false);
    }
    return (true);
}


function execProc2($conn, $procName, $dataIn)
{
    $procArgs = "@p1 INT";
    $procCode = "SET NOCOUNT ON; SELECT 199 IF @p1 = 0 RETURN 11 ELSE RETURN 22";
    $retValue = -1;
    $driverType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;

    $callArgs =  array(array(&$retValue, SQLSRV_PARAM_OUT, null, $driverType), 
                       array($dataIn, SQLSRV_PARAM_IN, null, $driverType));

    createProc($conn, $procName, $procArgs, $procCode);
    $stmt = callProcEx($conn, $procName, "? = ", "?", $callArgs);
    dropProc($conn, $procName);

    $row = sqlsrv_fetch_array($stmt);
    $count = count($row);
    sqlsrv_next_result($stmt);
    sqlsrv_free_stmt($stmt);

    if (($row === false) || ($count <= 0) || ($row[0] != 199) ||
        (($retValue != 11) && ($retValue != 22))) {
        trace("Row count = $count, Returned value = $retValue\n");
        return (false);
    }
    return (true);
}

try {
    storedProc();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Stored Proc Call" completed successfully.
