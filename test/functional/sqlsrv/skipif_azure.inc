<?php

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require 'MsSetup.inc';
if ($daasMode) die("skip test not applicable in Azure\n");

require 'MsCommon.inc';
if (isServerHGSEnabled()) {
    die("skip test not applicable in Azure VM\n");
}
?>