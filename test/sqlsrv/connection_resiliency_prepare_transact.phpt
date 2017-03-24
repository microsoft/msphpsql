--TEST--
Test connection resiliency with a prepared statement and transaction.
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break.php" );

$conn_break = sqlsrv_connect( $serverName, array( "Database"=>"$databaseName", "UID"=>"$username", "PWD"=>"$password") );

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$username", "PWD"=>"$password",
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10 );
                         
$conn = sqlsrv_connect( $serverName, $connectionInfo );
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

///////////////////////////////////////////////////
// Part 2 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$username", "PWD"=>"$password",
                         "ConnectRetryCount"=>11, "ConnectRetryInterval"=>10 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
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
     sqlsrv_commit( $conn );
     echo "Transaction was committed.\n";
}
else
{
     sqlsrv_rollback( $conn );
     echo "Transaction was rolled back.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////
// Part 3 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$username", "PWD"=>"$password",
                         "ConnectRetryCount"=>12, "ConnectRetryInterval"=>10 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
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
     sqlsrv_commit( $conn );
     echo "Transaction was committed.\n";
}
else
{
     sqlsrv_rollback( $conn );
     echo "Transaction was rolled back.\n";
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
Transaction was rolled back.
