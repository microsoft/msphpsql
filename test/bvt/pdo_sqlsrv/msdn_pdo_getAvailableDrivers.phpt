--TEST--
check if sqlsrv is in the array of available PDO drivers
--SKIPIF--

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