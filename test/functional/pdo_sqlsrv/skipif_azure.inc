<?php
if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv'))
    die("PDO driver cannot be loaded; skipping test.\n");
    
include 'MsCommon.inc';
if ( IsDaasMode() ) {
    die("skip test in Azure");
}
    
?>
