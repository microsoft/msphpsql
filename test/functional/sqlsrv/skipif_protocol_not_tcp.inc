<?php
if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

require_once( "MsSetup.inc" );

$connectionInfo = array( "UID"=>$userName, "PWD"=>$userPassword );

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    die( "skip Could not connect during SKIPIF." );
}

// Get process ID. Not the same as the one during the actual test, but
// we only need to know the protocol for a particular connection.
$stmt = sqlsrv_query( $conn, "SELECT @@SPID" );
if ( sqlsrv_fetch( $stmt ) )
{
    $spid = sqlsrv_get_field( $stmt, 0 );
}
else
{
    die("skip Could not fetch SPID.");
}

$stmt = sqlsrv_query( $conn, "SELECT * FROM sys.dm_exec_connections WHERE session_id = $spid");
if ( sqlsrv_fetch( $stmt ) )
{
    $prot = sqlsrv_get_field( $stmt, 3 );
    if ($prot != 'TCP')
    {
        die( "skip Not using a TCP protocol." );
    }
}
?>
