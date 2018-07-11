<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once( "MsSetup.inc" );

$conn = new PDO( "sqlsrv:server = $server ;", $uid, $pwd );
if( $conn === false )
{
    die( "skip Could not connect during SKIPIF." );
}

// Get process ID. Not the same as the one during the actual test, but
// we only need to know the protocol for a particular connection.
$stmt = $conn->query( "SELECT @@SPID" );
if ( $stmt )
{
    $spid = $stmt->fetch(PDO::FETCH_NUM)[0];
}
else
{
    die( "skip Could not fetch SPID during SKIPIF.");
}

$stmt = $conn->query( "SELECT * FROM sys.dm_exec_connections WHERE session_id = ".$spid);
$prot = $stmt->fetchColumn(3);

if ($prot != 'TCP')
{
    die("skip Not using a TCP protocol." );
}
?>
