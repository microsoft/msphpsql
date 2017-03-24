--TEST--
Test connection resiliency timeouts
--DESCRIPTION--
1. Connect with ConnectRetryCount equal to 0.
2. Reconnect with the default value of ConnectRetryCount(1).
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

$conn_break = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ;", "$username", "$password" );

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 0;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
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
    echo "Query successfully executed.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 1.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 2 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryInterval = 10;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
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
    echo "Query successfully executed.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 2.\n";
    print_r( $e->getMessage() );
}

$conn = null;
$conn_break = null;

?>
--EXPECTREGEX--
Error executing statement 1.
SQLSTATE\[08S02\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.
Query successfully executed.
