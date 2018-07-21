--TEST--
Test connection resiliency timeouts
--DESCRIPTION--
1. Connect with ConnectRetryCount equal to 0.
2. Reconnect with the default value of ConnectRetryCount (the default is 1).
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc');  ?>
--FILE--
<?php
require_once( "break.php" );

$conn_break = sqlsrv_connect( $server, array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd) );

///////////////////////////////////////////////////////////////////////////////
// Part 1 
// Expected to error out because ConnectRetryCount equals 0
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd,
                         "ConnectRetryCount"=>0 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

BreakConnection( $conn, $conn_break );

$stmt1 = sqlsrv_query( $conn, "SELECT * FROM $tableName1" );
if( $stmt1 === false )
{
    echo "Error in statement 1.\n";
    $err = sqlsrv_errors();
    if (strpos($err[0][0], '08S01')===false or
        (strpos($err[0][2], 'TCP Provider:')===false and strpos($err[0][2], 'SMux Provider:')===false and strpos($err[0][2], 'Session Provider:')===false)) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}
else
{
    echo "Statement 1 successful.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////
// Part 2 
// Expected to succeed with a single reconnection attempt
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd,
                         "ConnectRetryInterval"=>10 );

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

BreakConnection( $conn, $conn_break );

$stmt2 = sqlsrv_query( $conn, "SELECT * FROM $tableName1" );
if( $stmt2 === false )
{
    echo "Error in statement 2.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 2 successful.\n";
}

sqlsrv_close( $conn );
sqlsrv_close( $conn_break );

DropTables( $server, $uid, $pwd, $tableName1, $tableName2 )

?>
--EXPECTREGEX--
Error in statement 1.
Statement 2 successful.
