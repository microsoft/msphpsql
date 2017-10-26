--TEST--
Test PDO::__Construct by passing connection options
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $dsn = "ConnectionPooling = false;" .
           "APP = whatever;" .
           "LoginTimeout = 1;" .
           "ApplicationIntent = ReadOnly;" .
           "Encrypt = false;" .
           "Failover_Partner = whatever;" .
           "MultipleActiveResultSets = true;" .
           "MultiSubnetFailover = NO;" .
           "QuotedId = false;" .
           "TraceFile = whatever;" .
           "TraceOn = true;" .
           "TrustServerCertificate = false;" .
           "WSID = whatever;";
    $conn = connect($dsn);
    unset($conn);
    echo "Test Successful\n";
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
Test Successful
