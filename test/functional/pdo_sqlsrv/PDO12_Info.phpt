--TEST--
PDO PHP Info Test
--DESCRIPTION--
Verifies the functionality of PDO with phpinfo().
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
try {
    ob_start();
    phpinfo();
    $info = ob_get_contents();
    ob_end_clean();

    // Check phpinfo() data
    if (stristr($info, "PDO support => enabled") === false) {
        printf("PDO is not enabled\n");
    } elseif (stristr($info, "pdo_sqlsrv support => enabled") === false) {
        printf("Cannot find PDO_SQLSRV driver in phpinfo() output\n");
    } else {
        printf("Done\n");
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Done
