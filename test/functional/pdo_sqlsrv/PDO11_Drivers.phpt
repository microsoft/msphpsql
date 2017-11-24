--TEST--
PDO Drivers Info Test
--DESCRIPTION--
Verifies the functionality of "PDO:getAvailableDrivers()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
try {
    $drivers = PDO::getAvailableDrivers();
    if (!in_array("sqlsrv", $drivers)) {
        printf("$PhpDriver is missing.\n");
    } else {
        printf("Done\n");
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Done
