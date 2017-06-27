<?php

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

include 'MsCommon.inc';
if ( IsDaasMode() ) {
    die("test not applicable in Azure");
}

?>