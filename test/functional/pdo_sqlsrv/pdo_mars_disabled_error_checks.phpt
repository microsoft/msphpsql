--TEST--
Error checking for multiple active row sets (MARS) disabled
--DESCRIPTION--
This is similar to sqlsrv srv_053_mars_disabled_error_checks.phpt to check the errors 
when multiple active row sets (MARS) is disabled.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

try {
    $conn = new PDO("sqlsrv:server=$server; MultipleActiveResultSets = false", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql1 = "SELECT 'ONE'";
    $sql2 = "SELECT 'TWO'";
    
    $stmt1 = $conn->query($sql1);
    $stmt2 = $conn->query($sql2);
    $res = [$stmt1->fetch(), $stmt2->fetch()];
    var_dump($res);
    
    unset($stmt1);
    unset($stmt2);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

echo "\nDone\n";
?>
--EXPECT--
array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-61)
  [2]=>
  string(313) "The connection cannot process this operation because there is a statement with pending results.  To make the connection available for other queries, either fetch all results or cancel or free the statement.  For more information, see the product documentation about the MultipleActiveResultSets connection option."
}

Done
