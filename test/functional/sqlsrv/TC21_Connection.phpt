--TEST--
Connection Test
--DESCRIPTION--
Checks whether the driver can successfully establish a database connection.
Verifies as well that invalid connection attempts fail as expected.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function ConnectionTest()
{
    $testName = "Connection";
    startTest($testName);

    setup();

    // Invalid connection attempt => errors are expected
    $conn1 = sqlsrv_connect('InvalidServerName');
    if ($conn1 === false) {
        handleErrors();
    } else {
        die("Invalid connection attempt should have failed.");
    }

    // Valid connection attempt => no errors are expected
    $conn2 = connect();
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    if (!empty($errors)) {
        die("No errors were expected on valid connection attempts.");
    }
    sqlsrv_close($conn2);

    endTest($testName);
}

//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        ConnectionTest();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test "Connection" completed successfully.
