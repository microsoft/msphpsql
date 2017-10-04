--TEST--
functions return FALSE for errors.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsSetup.inc');

    $conn = sqlsrv_connect("_!@)(#");
    if ($conn !== false) {
        fatalError("sqlsrv_connect should have returned false.");
    }

    $conn = sqlsrv_connect("_!@#$", array( "Driver" => "Danica Patrick" ));
    if ($conn !== false) {
        fatalError("sqlsrv_connect should have returned false.");
    }

    $conn = sqlsrv_connect($server, array( "uid" => $uid , "pwd" => $pwd ));

    if ($conn === false) {
        fatalError("sqlsrv_connect should have connected.");
    }

    $stmt = sqlsrv_query($conn, "SELECT * FROM some_bogus_table");
    if ($stmt !== false) {
        fatalError("sqlsrv_query should have returned false.");
    }

    echo "Test successful.\n";
?>
--EXPECT--
Test successful.
