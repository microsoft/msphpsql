<?php
// Skip tests that require Azure SQL Database features on LocalDB
// LocalDB doesn't support the Authentication keyword

if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");

// Check if running on LocalDB by looking at server name
if (stripos($server, 'localdb') !== false) {
    die("skip Test requires Azure SQL Database or SQL Server with Azure AD, not supported on LocalDB");
}
?>
