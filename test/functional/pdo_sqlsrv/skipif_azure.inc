<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}
require 'MsSetup.inc';
if ($daasMode) {
    die("skip test not applicable in Azure\n");
}
