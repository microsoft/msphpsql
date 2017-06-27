<?php
if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv'))
    die("PDO driver cannot be loaded; skipping test.\n");
    
require 'MsSetup.inc';
if ($daasMode) die("skip test not applicable in Azure\n");
   
?>
