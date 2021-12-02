--TEST--
Tests error returned when binding output parameter with emulate prepare
--DESCRIPTION--
The test shows that the option sets in prepared statements overrides the 
connection setting of PDO::ATTR_EMULATE_PREPARES
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
<?php require('skipif_azure_dw.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

try {
    // Do not connect with AE enabled because otherwise this would have thrown a different exception
    $conn = new PDO("sqlsrv:server=$server; Database = $databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    $count = 0;

    $query = "select ? = count(* ) from cd_info";
    $stmt = $conn->prepare($query);
} catch (PDOException $e) {
    print("Error: " . $e->getMessage() . "\n");
}

try {
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    $int = 0;
    $bigint = 100;
    $query = "select ? = bigint_type, ? = int_type, ? = money_type from [test_types] where int_type < 0";
    $stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
} catch (PDOException $e) {
    print("Error: " . $e->getMessage() . "\n");
}

// free the statement and connection
unset($stmt);
unset($conn);
?>
--EXPECT--
Error: SQLSTATE[IMSSP]: Statement with emulate prepare on does not support output or input_output parameters.
Error: SQLSTATE[IMSSP]: Statement with emulate prepare on does not support output or input_output parameters.
