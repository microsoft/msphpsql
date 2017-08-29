<?php

if (!extension_loaded("sqlsrv")) 
    die("skip extension not loaded");

require_once( "MsSetup.inc" );
$connectionInfo = array( "UID"=>$userName, "PWD"=>$userPassword );

$conn = sqlsrv_connect( $server, $connectionInfo );
if( ! $conn )
{
    echo ( "Error: could not connect during SKIPIF!" );
}
else 
{
    $server_info = sqlsrv_server_info( $conn );  
    if( $server_info )  
    {  
        // check SQL Server version
        $version = substr($server_info['SQLServerVersion'], 0, 2);
        if ($version < 13)  
        {
            // older than SQL Server 2016
            die( "skip - feature not supported in this version of SQL Server." );
        }
    }
    
    $client_info = sqlsrv_client_info( $conn );
    if( $client_info )
    {
        // check ODBC driver version
        $version = substr($client_info['DriverVer'], 0, 2);
        if ($version < 13)
        {
            // older than ODBC 13
            die( "skip - feature not supported in this version of ODBC driver." );
        }
    }
}  
?>