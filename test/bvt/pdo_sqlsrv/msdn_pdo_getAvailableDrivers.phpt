--TEST--
returns an array of PDO drivers
--SKIPIF--

--FILE--
<?php
print_r(PDO::getAvailableDrivers());
?>
--EXPECT--
Array
(
    [0] => sqlsrv
)