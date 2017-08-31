<?php

if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv'))
    die("PDO driver cannot be loaded; skipping test.\n");

require_once( "MsSetup.inc" );
$conn = new PDO("sqlsrv:server = $server;", $uid, $pwd );
if( ! $conn )
{
    echo ( "Error: could not connect during SKIPIF!" );
}
else 
{
    $attr = $conn->getAttribute(constant('PDO::ATTR_SERVER_VERSION'));
    $version = substr($attr, 0, 2);
    if ($version < 13)
    {
        // older than SQL Server 2016
        die( "skip - feature not supported in this version of SQL Server." );
    }
    
    // check ODBC driver version
    $attr = $conn->getAttribute(constant('PDO::ATTR_CLIENT_VERSION'));
    $version = substr($attr['DriverVer'], 0, 2);
    if ($version < 13)
    {
        // older than ODBC 13
        die( "skip - feature not supported in this version of ODBC driver." );
    }
}
?>