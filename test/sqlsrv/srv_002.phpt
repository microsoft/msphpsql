--TEST--
Connect with options
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

$conn = Connect();
if( !$conn ) {
    FatalError("Connection could not be established.\n");
}
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
Done
