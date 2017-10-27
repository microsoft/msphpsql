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

function bugRepro()
{
    $testName = "Regression VSTS 611146";
    startTest($testName);

    setup();
    $conn1 = AE\connect();
    // empty parameter array
    $s = sqlsrv_query($conn1, "select ?", array( array() ));
    if ($s !== false) {
        die("Should have failed.");
    } else {
        $arr = sqlsrv_errors();
        print_r($arr[0][2]);
        print_r("\n");
    }


    // unknown direction
    $s = sqlsrv_query($conn1, "select ?", array( array( 1, 1000 ) ));
    if ($s !== false) {
        die("Should have failed.");
    } else {
        $arr = sqlsrv_errors();
        print_r($arr[0][2]);
        print_r("\n");
    }

    endTest($testName);
}

try {
    bugRepro();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Parameter array 1 must have at least one value or variable.
An invalid direction for parameter 1 was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.
Test "Regression VSTS 611146" completed successfully.
