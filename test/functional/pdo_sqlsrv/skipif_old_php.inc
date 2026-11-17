<?php

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

if (PHP_VERSION_ID < 70200) {
    die("skip This test requires 7.2 or above");
}
?>
