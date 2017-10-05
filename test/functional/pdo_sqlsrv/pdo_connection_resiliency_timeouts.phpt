--TEST--
Test connection resiliency timeouts
--DESCRIPTION--
1. Connect with ConnectRetryCount equal to 0.
2. Reconnect with the default value of ConnectRetryCount(1).
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc'); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

$conn_break = new PDO( "sqlsrv:server = $server ; Database = $dbName ;", $uid, $pwd );

///////////////////////////////////////////////////////////////////////////////
// Part 1 
// Expected to error out because ConnectRetryCount equals 0
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 0;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", $uid, $pwd );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

BreakConnection( $conn, $conn_break );

try
{
    $stmt1 = $conn->query( "SELECT * FROM $tableName1" );
    if ( $stmt1 ) echo "Query successfully executed.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 1.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////////////////////////////////
// Part 2 
// Expected to succeed with a single reconnection attempt
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = "ConnectRetryInterval = 10;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", $uid, $pwd );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

BreakConnection( $conn, $conn_break );

try
{
    $stmt2 = $conn->query( "SELECT * FROM $tableName1" );
    if ( $stmt2 ) echo "Query successfully executed.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 2.\n";
    print_r( $e->getMessage() );
}

$conn = null;
$conn_break = null;

DropTables( $server, $uid, $pwd, $tableName1, $tableName2 );

?>
--EXPECTREGEX--
Error executing statement 1.
SQLSTATE\[08S02\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.
Query successfully executed.
