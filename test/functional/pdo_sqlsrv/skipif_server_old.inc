<?php

if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv'))
    die("PDO driver cannot be loaded; skipping test.\n");

require_once( "MsSetup.inc" );
$conn = new PDO("sqlsrv:server = $server;", $uid, $pwd );
if( ! $conn )
{
    die( "skip - could not connect during SKIPIF." );
}

$attr = $conn->getAttribute(constant('PDO::ATTR_SERVER_VERSION'));
$version = substr($attr, 0, 2);
if ($version < 13)
{
    die( "skip - feature not supported in this version of SQL Server." );
}
?>