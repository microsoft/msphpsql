<?php

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require_once('MsCommon.inc');
if (isAzureDW()) {
    die("skip test not applicable in Azure Data Warehouse\n");
}

?>