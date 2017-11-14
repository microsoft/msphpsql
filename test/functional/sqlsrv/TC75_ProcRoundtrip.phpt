--TEST--
Data Roundtrip via Stored Proc
--DESCRIPTION--
Verifies that data is not corrupted through a roundtrip via a store procedure.
Checks all character data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function storedProcRoundtrip($minType, $maxType)
{
    $testName = "Stored Proc Roundtrip";
    startTest($testName);

    setup();
    $tableName = 'TC75test';
    $procName = "TC75test_proc";
    $conn1 = AE\connect();

    $data = "The quick brown fox jumps over the lazy dog 0123456789";
    $dataSize = strlen($data);

    for ($i = $minType; $i <= $maxType; $i++) {
        $dataTypeIn = getSqlType($i);
        $phpTypeIn = getSqlsrvSqlType($i, $dataSize);

        for ($j = $minType; $j <= $maxType; $j++) {
            $k = $j;
            switch ($j) {   // avoid LOB types as output
                case 14:        // varchar(max)
                case 18:        // text
                    $k = 13;    // varchar
                    break;

                case 17:        // nvarchar(max)
                case 19:        // ntext
                    $k = 16;    // nvarchar
                    break;

                default:
                    break;

            }
            $dataTypeOut = getSqlType($k);
            $phpTypeOut = getSqlsrvSqlType($k, 512);
            execProcRoundtrip($conn1, $procName, $dataTypeIn, $dataTypeOut, $phpTypeIn, $phpTypeOut, $data);
        }
    }

    sqlsrv_close($conn1);

    endTest($testName);
}


function execProcRoundtrip($conn, $procName, $dataTypeIn, $dataTypeOut, $phpTypeIn, $phpTypeOut, $dataIn)
{
    $procArgs = "@p1 $dataTypeIn, @p2 $dataTypeOut OUTPUT";
    $procCode = "SELECT @p2 = CONVERT($dataTypeOut, @p1)";
    createProc($conn, $procName, $procArgs, $procCode);

    $callArgs = "?, ?";
    $callResult = "";
    $callValues = array(array($dataIn, SQLSRV_PARAM_IN, null, $phpTypeIn),
                array(&$callResult, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), $phpTypeOut));
    callProc($conn, $procName, $callArgs, $callValues);
    dropProc($conn, $procName);

    $dataOut = trim($callResult);

    if (strncmp($dataOut, $dataIn, strlen($dataIn)) != 0) {
        traceData($dataTypeIn."=>".$dataTypeOut, "\n In: [".$dataIn."]\nOut: [".$dataOut."]");
        die("Unexpected result for ".$dataTypeIn."=>".$dataTypeOut);
    }
}

try {
    storedProcRoundtrip(12, 19);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Stored Proc Roundtrip" completed successfully.
