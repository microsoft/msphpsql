<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once('MsCommon_mid-refactor.inc');
if (isAzureDW()) {
    die("skip test not applicable in Azure Data Warehouse\n");
}
?>