--TEST--
Test connection resiliency with a prepared statement and transaction.
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

$conn_break = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ;", "$username", "$password" );

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10;";

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

try
{
    $stmt1 = $conn->prepare( "SELECT * FROM $tableName1" );
    echo "Statement 1 prepared.\n";
}
catch( PDOException $e )
{
    echo "Error preparing statement 1.\n";
    print_r( $e->getMessage() );
}

BreakConnection( $conn, $conn_break );

try
{
    $stmt1->execute();
    echo "Statement 1 executed.\n";
}
catch( PDOException $e )
{
    echo "Error executing prepared query.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 2 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 11; ConnectRetryInterval = 10;";

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
    $conn->beginTransaction();
    echo "Transaction begun.\n";
}
catch( PDOException $e )
{
    print_r( $e->getMessage() );
    echo "Could not begin transaction.\n";
}

$tsql = "INSERT INTO $tableName1 VALUES ( 700, 'zyxwv' )";

try
{
    $stmt2 = $conn->query( $tsql );

    if ( $stmt2 )
    {
        $conn->commit();
        echo "Transaction was committed.\n";
    }
    else
    {
        $conn->rollBack();
        echo "Transaction was rolled back.\n";
    }
}
catch ( PDOException $e )
{
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 3 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 12; ConnectRetryInterval = 10;";

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

try
{
    $conn->beginTransaction();
    echo "Transaction begun.\n";
}
catch( PDOException $e )
{
    print_r( $e->getMessage() );
    echo "Could not begin transaction.\n";
}

BreakConnection( $conn, $conn_break );

$tsql = "INSERT INTO $tableName1 VALUES ( 700, 'zyxwv' )";

try
{
    $stmt2 = $conn->query( $tsql );

    if ( $stmt2 )
    {
        $conn->commit();
        echo "Transaction was committed.\n";
    }
    else
    {
        $conn->rollBack();
        echo "Transaction was rolled back.\n";
    }
}
catch ( PDOException $e )
{
    print_r( $e->getMessage() );
}

// This try catch block prevents an Uncaught PDOException error that occurs
// when trying to free the connection.
try
{
    $conn = null;
}
catch ( PDOException $e )
{
    print_r( $e->getMessage() );
}

$conn_break = null;

?>
--EXPECTREGEX--
Statement 1 prepared.
Statement 1 executed.
Transaction begun.
Transaction was committed.
Transaction begun.
SQLSTATE\[08S02\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.
SQLSTATE\[08S01\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Communication link failure