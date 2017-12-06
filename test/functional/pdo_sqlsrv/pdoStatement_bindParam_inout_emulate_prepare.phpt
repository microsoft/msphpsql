--TEST--
Tests error returned when binding input/output parameter with emulate prepare
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
try {
    $dbh = connect();

    $procName = 'sp_ReverseString';
    dropProc($dbh, $procName);
    $dbh->query("CREATE PROCEDURE $procName @String as VARCHAR(2048) OUTPUT as SELECT @String = REVERSE(@String)");
    $stmt = $dbh->prepare("EXEC $procName ?", array(PDO::ATTR_EMULATE_PREPARES => true));
    $string = "123456789";
    $stmt->bindParam(1, $string, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2048);
    $stmt->execute();
    print "Result: $string";

    //free the statement and connection
    dropProc($dbh, $procName);
    unset($stmt);
    unset($dbh);
} catch (PDOException $e) {
    $error = $e->getMessage();
    $pass = !isAEConnected() && $error === "SQLSTATE[IMSSP]: Statement with emulate prepare on does not support output or input_output parameters.";
    $pass |= isAEConnected() && ($error === "SQLSTATE[IMSSP]: Parameterized statement with attribute PDO::ATTR_EMULATE_PREPARES is not supported in a Column Encryption enabled Connection.");
    if (!$pass) {
        print("Error: " . $error . "\n");
    } else {
        print("Test successfully done\n");
    }
}
?>
--EXPECT--
Test successfully done
