--TEST--
Connection recovery test
--DESCRIPTION--
Connect and execute a command, kill the connection, execute another command.
Then do it again without a buffered result set, by freeing the statement before
killing the connection and then not freeing it. The latter case is the only one
that should fail. Finally, execute two queries in two threads on a recovered
non-MARS connection. This should fail too.
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc');  ?>
--FILE--
<?php
// There is a lot of repeated code here that could be refactored with helper methods,
// mostly for statement allocation. But that would affect scoping for the $stmt variables,
// which could affect the results when attempting to reconnect. What happens to statements
// when exiting the helper method? Do the associated cursors remain active? It is an
// unnecessary complication, so I have left the code like this.

require_once( "break.php" );

$conn_break = sqlsrv_connect( $server, array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd) );

///////////////////////////////////////////////////////////////////////////////
// Part 1 
// Expected to successfully execute second query because buffered cursor for
// first query means connection is idle when broken
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd, "ConnectionPooling"=>false,
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

$stmt1 = sqlsrv_query( $conn, "SELECT * FROM $tableName1", array(), array( "Scrollable"=>"buffered" ) );
if( $stmt1 === false )
{
    echo "Error in statement 1.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 1 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt1 );
    echo $rowcount." rows in result set.\n";
}

BreakConnection( $conn, $conn_break );

$stmt2 = sqlsrv_query( $conn, "SELECT * FROM $tableName2", array(), array( "Scrollable"=>"buffered" ) );
if( $stmt2 === false )
{
    echo "Error in statement 2.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 2 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt2 );
    echo $rowcount." rows in result set.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////
// Part 2 
// Expected to successfully execute second query because first statement is
// freed before breaking connection
///////////////////////////////////////////////////////////////////////////////

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

$stmt3 = sqlsrv_query( $conn, "SELECT * FROM $tableName1" );
if( $stmt3 === false )
{
    echo "Error in statement 3.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 3 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt3 );
    echo $rowcount." rows in result set.\n";
}

sqlsrv_free_stmt( $stmt3 );

BreakConnection( $conn, $conn_break );

$stmt4 = sqlsrv_query( $conn, "SELECT * FROM $tableName2" );
if( $stmt4 === false )
{
    echo "Error in statement 4.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 4 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt4 );
    echo $rowcount." rows in result set.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////
// Part 3 
// Expected to fail executing second query because default cursor for first
// query is still active when connection is broken
///////////////////////////////////////////////////////////////////////////////

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

$stmt5 = sqlsrv_query( $conn, "SELECT * FROM $tableName1" );
if( $stmt5 === false )
{
    echo "Error in statement 5.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 5 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt5 );
    echo $rowcount." rows in result set.\n";
}

BreakConnection( $conn, $conn_break );

$stmt6 = sqlsrv_query( $conn, "SELECT * FROM $tableName2" );
if( $stmt6 === false )
{
    echo "Error in statement 6.\n";
    $err = sqlsrv_errors();
    if (strpos($err[0][0], '08S01')===false or
        (strpos($err[0][2], 'TCP Provider:')===false and strpos($err[0][2], 'SMux Provider:')===false and strpos($err[0][2], 'Session Provider:')===false)) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}
else
{
    echo "Statement 6 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt6 );
    echo $rowcount." rows in result set.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////
// Part 4 
// Expected to trigger an error because there are two active statements with
// pending results and MARS is off
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = array( "Database"=>$dbName, "UID"=>$uid, "PWD"=>$pwd,
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10, "MultipleActiveResultSets"=>false );

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

BreakConnection( $conn, $conn_break );

$stmt7 = sqlsrv_query( $conn, "SELECT * FROM $tableName1" );
if( $stmt7 === false )
{
    echo "Error in statement 7.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 7 successful.\n";
}

$stmt8 = sqlsrv_query( $conn, "SELECT * FROM $tableName2" );
if( $stmt8 === false )
{
    echo "Error in statement 8.\n";
    $err = sqlsrv_errors();
    if (strpos($err[0][0], 'IMSSP')===false or
        strpos($err[0][2], 'The connection cannot process this operation because there is a statement with pending results')===false) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}
else
{
    echo "Statement 8 successful.\n";
}

sqlsrv_close( $conn );
sqlsrv_close( $conn_break );

?>
--EXPECT--
Statement 1 successful.
16 rows in result set.
Statement 2 successful.
9 rows in result set.
Statement 3 successful.
 rows in result set.
Statement 4 successful.
 rows in result set.
Statement 5 successful.
 rows in result set.
Error in statement 6.
Statement 7 successful.
Error in statement 8.
