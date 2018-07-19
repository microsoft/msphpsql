--TEST--
Test connection resiliency with a prepared statement and transaction.
--DESCRIPTION--
Prepare a statement, break the connection, and execute the statement. Then
test transactions by breaking the connection before beginning a transaction
and in the middle of the transaction. The latter case should fail.
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc');  ?>
--FILE--
<?php
require_once( "break_pdo.php" );

$conn_break = new PDO( "sqlsrv:server = $server ; Database = $dbName ;", $uid, $pwd );

///////////////////////////////////////////////////////////////////////////////
// Part 1 
// Statement expected to be executed because the connection is idle after
// statement has been prepared
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10;";

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

try
{
    $stmt1 = $conn->prepare( "SELECT * FROM $tableName1" );
    if ( $stmt1 ) echo "Statement 1 prepared.\n";
    else echo "Error preparing statement 1.\n";
}
catch( PDOException $e )
{
    echo "Exception preparing statement 1.\n";
    print_r( $e->getMessage() );
}

BreakConnection( $conn, $conn_break );

try
{
    if ( $stmt1->execute() ) echo "Statement 1 executed.\n";
    else echo "Statement 1 failed.\n";
}
catch( PDOException $e )
{
    echo "Exception executing statement 1.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////////////////////////////////
// Part 2 
// Transaction should be committed because connection is broken before
// transaction begins
///////////////////////////////////////////////////////////////////////////////

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
    if ( $conn->beginTransaction() ) echo "Transaction begun.\n";
    else echo "Could not begin transaction.\n";
}
catch( PDOException $e )
{
    print_r( $e->getMessage() );
    echo "Exception: could not begin transaction.\n";
}

$tsql = "INSERT INTO $tableName1 VALUES ( 700, 'zyxwv' )";

try
{
    $stmt2 = $conn->query( $tsql );

    if ( $stmt2 )
    {
        if ( $conn->commit() )
        {
            echo "Transaction was committed.\n";
        }
        else
        {
            echo "Statement valid but commit failed.\n";
            print_r( sqlsrv_errors() );
        }
    }
    else
    {
        if ( $conn->rollBack() )
        {
            echo "Transaction was rolled back.\n";
        }
        else
        {
            echo "Statement not valid and rollback failed.\n";
            print_r( sqlsrv_errors() );
        }
    }
}
catch ( PDOException $e )
{
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////////////////////////////////
// Part 3 
// Expected to trigger an error because connection is interrupted in the middle
// of a transaction
///////////////////////////////////////////////////////////////////////////////

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

try
{
    if ( $conn->beginTransaction() ) echo "Transaction begun.\n";
    else echo "Could not begin transaction.\n";
}
catch( PDOException $e )
{
    print_r( $e->getMessage() );
    echo "Exception: could not begin transaction.\n";
}

BreakConnection( $conn, $conn_break );

$tsql = "INSERT INTO $tableName1 VALUES ( 700, 'zyxwv' )";

try
{
    $stmt2 = $conn->query( $tsql );

    if ( $stmt2 )
    {
        if ( $conn->commit() )
        {
            echo "Transaction was committed.\n";
        }
        else
        {
            echo "Statement valid but commit failed.\n";
            print_r( sqlsrv_errors() );
        }
    }
    else
    {
        if ( $conn->rollBack() )
        {
            echo "Transaction was rolled back.\n";
        }
        else
        {
            echo "Statement not valid and rollback failed.\n";
            print_r( sqlsrv_errors() );
        }
    }
}
catch ( PDOException $e )
{
    echo "Transaction failed.\n";
    $err = $e->getMessage();
    if (strpos($err, 'SQLSTATE[08S02]')===false or (strpos($err, 'TCP Provider')===false and strpos($err, 'SMux Provider')===false)) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}

// This try catch block prevents an Uncaught PDOException error that occurs
// when trying to free the connection.
try
{
    $conn = null;
}
catch ( PDOException $e )
{
    $err = $e->getMessage();
    if (strpos($err, 'SQLSTATE[08S01]')===false or strpos($err, 'Communication link failure')===false) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}

$conn_break = null;

?>
--EXPECT--
Statement 1 prepared.
Statement 1 executed.
Transaction begun.
Transaction was committed.
Transaction begun.
Transaction failed.
