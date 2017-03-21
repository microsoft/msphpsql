--TEST--
Test connection resiliency timeouts
--DESCRIPTION--
1. Connect with ConnectRetryCount equal to 0.
2. Reconnect with the default value of ConnectRetryCount( 1 ).
3. Test with a QueryTimeout longer than ConnectRetryCount*( ConnectRetryInterval+LoginTimeout ).
4. Test QueryTimeout when attempting to recover a connection when the query timeout is less than the total time for connection recovery, given by ConnectRetryCount*ConnectRetryInterval
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

StartMSSQLServer( $serverName );

$connectionInfo = "ConnectRetryCount = 0;";

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

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

RestartMSSQLServer( $serverName );

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

RestartMSSQLServer( $serverName );

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

///////////////////////////////////////////////////
// Part 3 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 3; ConnectRetryInterval = 5; LoginTimeout = 5;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $conn->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 60 );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

StopMSSQLServer( $serverName );

try
{
    $stmt3 = $conn->query( "SELECT * FROM $tableName1" );
    echo "Query successfully executed.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 3.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 4 /////////////////////////////////////////
///////////////////////////////////////////////////

StartMSSQLServer( $serverName );

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10; MultipleActiveResultSets = false;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $conn->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 30 );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

StopMSSQLServer( $serverName );

try
{
    $stmt4 = $conn->query( "SELECT * FROM $tableName1" );
    echo "Query executed.\n";
}
catch( PDOException $e )
{
    echo "\nError executing statement 4.\n";
    print_r( $e->getMessage() );
}

StartMSSQLServer( $serverName );

$conn = null;

?>
--EXPECTREGEX--
Error executing statement 1.
SQLSTATE\[08S01\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.
Query successfully executed.
Error executing statement 3.
SQLSTATE\[08S01\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Communication link failure
Error executing statement 4.
SQLSTATE\[HYT00\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired
