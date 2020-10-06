--TEST--
check if sqlsrv is in the array of available PDO drivers
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
$drivers = PDO::getAvailableDrivers();
if (in_array('sqlsrv', $drivers))
    echo "sqlsrv found\n";
else
    echo "sqlsrv not found\n";
echo "Done\n";
?>
--EXPECT--
sqlsrv found
Done