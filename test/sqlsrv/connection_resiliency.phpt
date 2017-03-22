--TEST--
Connection recovery test
--DESCRIPTION--
Connect and execute a command, kill the connection, execute another command. Then do it again without a buffered result set, by freeing the statement and then not freeing it. The latter case is the only one that should fail. Finally, execute two queries in two threads on a recovered non-MARS connection. This should fail too.
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break.php" );

$conn_break = sqlsrv_connect( $serverName, array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password") );

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10 );
                         
$conn = sqlsrv_connect( $serverName, $connectionInfo );
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

///////////////////////////////////////////////////
// Part 2 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>11, "ConnectRetryInterval"=>10 );
                         
$conn = sqlsrv_connect( $serverName, $connectionInfo );
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

///////////////////////////////////////////////////
// Part 3 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>12, "ConnectRetryInterval"=>10 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
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
     print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 6 successful.\n";
    $rowcount = sqlsrv_num_rows( $stmt6 );
    echo $rowcount." rows in result set.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////
// Part 4 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10, "MultipleActiveResultSets"=>false );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
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
     print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 8 successful.\n";
}

sqlsrv_close( $conn );
sqlsrv_close( $conn_break );

?>
--EXPECTREGEX--
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
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08S02
            \[SQLSTATE\] => 08S02
            \[1\] => 10054
            \[code\] => 10054
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.

            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.

        \)

    \[1\] => Array
        \(
            \[0\] => 08S02
            \[SQLSTATE\] => 08S02
            \[1\] => 10054
            \[code\] => 10054
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Unable to open a logical session
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Unable to open a logical session
        \)

\)
Statement 7 successful.
Error in statement 8.
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -44
            \[code\] => -44
            \[2\] => The connection cannot process this operation because there is a statement with pending results.  To make the connection available for other queries, either fetch all results or cancel or free the statement.  For more information, see the product documentation about the MultipleActiveResultSets connection option.
            \[message\] => The connection cannot process this operation because there is a statement with pending results.  To make the connection available for other queries, either fetch all results or cancel or free the statement.  For more information, see the product documentation about the MultipleActiveResultSets connection option.
        \)

    \[1\] => Array
        \(
            \[0\] => HY000
            \[SQLSTATE\] => HY000
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Connection is busy with results for another command
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Connection is busy with results for another command
        \)

\)
