--TEST--
Test connection resiliency with a prepared statement and transaction.
--DESCRIPTION--
Prepare a statement, break the connection, and execute the statement. Then
test transactions by breaking the connection before beginning a transaction
and in the middle of the transaction. The latter case should fail (i.e., the
transaction should be rolled back).
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc');  ?>
--FILE--
<?php
require_once( "break.php" );

$conn_break = sqlsrv_connect( $server, array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd) );

///////////////////////////////////////////////////////////////////////////////
// Part 1 
// Statement expected to be executed because the connection is idle after
// statement has been prepared
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd, "ConnectionPooling"=>false,
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

$stmt1 = sqlsrv_prepare( $conn, "SELECT * FROM $tableName1" );
if( $stmt1 === false )
{
    echo "Error in statement preparation.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 1 prepared.\n";
}

BreakConnection( $conn, $conn_break );

if( sqlsrv_execute( $stmt1 ) )
{
    echo "Statement 1 executed.\n";
}
else
{
    echo "Statement 1 could not be executed.\n";
    print_r( sqlsrv_errors() );
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////
// Part 2 
// Transaction should be committed because connection is broken before
// transaction begins
///////////////////////////////////////////////////////////////////////////////

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

BreakConnection( $conn, $conn_break );

if ( sqlsrv_begin_transaction( $conn ) === false )
{
    echo "Could not begin transaction.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Transaction begun.\n";
}

$number = 700;
$string = 'zxywv';

$tsql = "INSERT INTO $tableName1 VALUES ( ?, ? )";
$params = array( $number, $string );
$stmt2 = sqlsrv_query( $conn, $tsql, $params );

if( $stmt2 )
{
    if ( sqlsrv_commit( $conn ) )
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
    if ( sqlsrv_rollback( $conn ) )
    {
        echo "Transaction was rolled back.\n";
    }
    else
    {
        echo "Statement not valid and rollback failed.\n";
        print_r( sqlsrv_errors() );
    }
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////
// Part 3 
// Expected to trigger an error because connection is interrupted in the middle
// of a transaction
///////////////////////////////////////////////////////////////////////////////

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

if ( sqlsrv_begin_transaction( $conn ) === false )
{
    echo "Could not begin transaction.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Transaction begun.\n";
}

BreakConnection( $conn, $conn_break );

$number = 700;
$string = 'zxywv';

$tsql = "INSERT INTO $tableName1 VALUES ( ?, ? )";
$params = array( $number, $string );
$stmt2 = sqlsrv_query( $conn, $tsql, $params );

if( $stmt2 )
{
    if ( sqlsrv_commit( $conn ) )
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
    if ( sqlsrv_rollback( $conn ) )
    {
        echo "Transaction was rolled back.\n";
    }
    else
    {
        echo "Statement not valid and rollback failed.\n";
        $err = sqlsrv_errors();
        if (strpos($err[0][0], '08S02')===false or
            (strpos($err[0][2], 'TCP Provider:')===false and strpos($err[0][2], 'SMux Provider:')===false and strpos($err[0][2], 'Session Provider:')===false)) {
            echo "Error: Wrong error message.\n";
            print_r($err);
        }
    }
}

sqlsrv_close( $conn );
sqlsrv_close( $conn_break );
?>
--EXPECT--
Statement 1 prepared.
Statement 1 executed.
Transaction begun.
Transaction was committed.
Transaction begun.
Statement not valid and rollback failed.
