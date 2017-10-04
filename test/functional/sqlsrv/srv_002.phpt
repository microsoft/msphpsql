--TEST--
connect with options
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

$conn = connect();
if (!$conn) {
    fatalError("Connection could not be established.\n");
}
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
Done
