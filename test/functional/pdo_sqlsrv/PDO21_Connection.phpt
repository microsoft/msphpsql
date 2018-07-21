--TEST--
PDO Connection Test
--DESCRIPTION--
Checks whether the driver can successfully establish a database connection.
Verifies as well that invalid connection attempts fail as expected.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");
try {
    // Invalid connection attempt => errors are expected
    $serverName="InvalidServerName";

    $dsn = getDSN($serverName, $databaseName, $driver);
    $conn1 = new PDO($dsn, $uid, $pwd, $connectionOptions);
    if ($conn1) {
        printf("Invalid connection attempt should have failed.\n");
    }
    unset($conn1);
} catch (Exception $e) {
    unset($conn1);
    echo "Done\n";
}
try {
    // Valid connection attempt => no errors are expected
    $conn2 = connect();
    unset($conn2);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Done
