<?php
if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv'))
    die("PDO driver cannot be loaded; skipping test.\n");
    
require_once( "MsSetup.inc" );
require_once( "MsCommon_mid-refactor.inc" );

$conn = new PDO( "sqlsrv:server = $server;", $uid, $pwd );
if( ! $conn )
{
    echo( "Error: could not connect during SKIPIF!" );
}
else if(isColEncrypted() && !isAEQualified($conn))
{
    die( "skip - AE feature not supported in the current environment." );
}
?>
