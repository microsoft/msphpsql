--TEST--
Connect to the default database with credentials
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

$conn = Connect();

if( !$conn ) {
    echo "Connection could not be established.\n";
    die( print_r( sqlsrv_errors(), true));
}
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
Done
