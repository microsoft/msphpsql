--TEST--
Test the PDO::quote() method with a string containing '\0' character
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

try {
    $connection = connect();

    $str = "XX\0XX";

    print("Original: " . str_replace("\0", "{NUL}", $str) . "\n");
    $str = $connection->quote($str);
    print("Quoted:   " . str_replace("\0", "{NUL}", $str) . "\n");
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}
?>
--EXPECT--
Original: XX{NUL}XX
Quoted:   'XX{NUL}XX'
