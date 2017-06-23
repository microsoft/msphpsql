--TEST--
Tests error returned when binding input/output parameter with emulate prepare
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
$dsn = "sqlsrv:Server=$server ; Database = $databaseName";
try {
    $dbh = new PDO($dsn, $uid, $pwd);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $dbh->query("IF OBJECT_ID('sp_ReverseString', 'P') IS NOT NULL DROP PROCEDURE sp_ReverseString");
    $dbh->query("CREATE PROCEDURE sp_ReverseString @String as VARCHAR(2048) OUTPUT as SELECT @String = REVERSE(@String)");
    $stmt = $dbh->prepare("EXEC sp_ReverseString ?", array(PDO::ATTR_EMULATE_PREPARES => true));
    $string = "123456789";
    $stmt->bindParam(1, $string, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2048);
    $stmt->execute();
    print "Result: ".$string;

    //free the statement and connection
    $stmt = null;
    $dbh = null;
}
catch(PDOException $e) {
    print("Error: " . $e->getMessage() . "\n");
}
?>
--EXPECT--
Error: SQLSTATE[IMSSP]: Statement with emulate prepare on does not support output or input_output parameters.