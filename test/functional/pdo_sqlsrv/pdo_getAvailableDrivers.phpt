--TEST--
Test the PDO::getAvailableDrivers() method.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
try {
    // Do not print anything, as the result will be different for each computer
    $result = PDO::getAvailableDrivers();
    echo "Test successful.\n";
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
Test successful.
