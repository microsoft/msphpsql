<?php

if (!extension_loaded("sqlsrv")) 
    die("skip extension not loaded");

require_once( "MsSetup.inc" );
$connectionInfo = array( "UID"=>$userName, "PWD"=>$userPassword );

$conn = sqlsrv_connect( $server, $connectionInfo );
if( ! $conn )
{
    die( "skip - could not connect during SKIPIF." );
}

$server_info = sqlsrv_server_info( $conn );  
if( $server_info )  
{  
    // check SQL Server version
    $version = substr($server_info['SQLServerVersion'], 0, 2);
    if ($version < 13)
    {
        die( "skip - feature not supported in this version of SQL Server." );
    }
}  
?>