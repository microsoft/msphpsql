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
require_once( "break.php" );

StartMSSQLServer( $serverName );

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>0 );

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

RestartMSSQLServer( $serverName );

$stmt1 = sqlsrv_query( $conn, "SELECT * FROM $tableName1" );
if( $stmt1 === false )
{
     echo "Error in statement 1.\n";
     print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 1 successful.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////
// Part 2 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryInterval"=>10 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

RestartMSSQLServer( $serverName );

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

///////////////////////////////////////////////////
// Part 3 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>3, "ConnectRetryInterval"=>5, "LoginTimeout"=>5 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

StopMSSQLServer( $serverName );

$stmt3 = sqlsrv_query( $conn, "SELECT * FROM $tableName1", array(), array( 'QueryTimeout'=>120 ) );
if( $stmt3 === false )
{
     echo "Error in statement 3.\n";
     print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 3 successful.\n";
}

sqlsrv_close( $conn );

///////////////////////////////////////////////////
// Part 4 /////////////////////////////////////////
///////////////////////////////////////////////////

StartMSSQLServer( $serverName );

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>10, "MultipleActiveResultSets"=>false );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect.\n";
    print_r( sqlsrv_errors() );
}

StopMSSQLServer( $serverName );

$stmt4 = sqlsrv_query( $conn, "SELECT * FROM $tableName1", array(), array( 'QueryTimeout'=>30 ) );
if( $stmt4 === false )
{
     echo "Error in statement 4.\n";
     print_r( sqlsrv_errors() );
}
else
{
    echo "Statement 4 successful.\n";
}

sqlsrv_close( $conn );

StartMSSQLServer( $serverName );
?>
--EXPECTREGEX--
Error in statement 1.
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08S01
            \[SQLSTATE\] => 08S01
            \[1\] => 10054
            \[code\] => 10054
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.

            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.

        \)

    \[1\] => Array
        \(
            \[0\] => 08S01
            \[SQLSTATE\] => 08S01
            \[1\] => 10054
            \[code\] => 10054
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Communication link failure
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Communication link failure
        \)

\)
Statement 2 successful.
Error in statement 3.
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08S01
            \[SQLSTATE\] => 08S01
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Communication link failure
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Communication link failure
        \)

    \[1\] => Array
        \(
            \[0\] => IMC01
            \[SQLSTATE\] => IMC01
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]The connection is broken and recovery is not possible. The client driver attempted to recover the connection one or more times and all attempts failed. Increase the value of ConnectRetryCount to increase the number of recovery attempts.
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]The connection is broken and recovery is not possible. The client driver attempted to recover the connection one or more times and all attempts failed. Increase the value of ConnectRetryCount to increase the number of recovery attempts.
        \)

\)
Error in statement 4.
Array
\(
    \[0\] => Array
        \(
            \[0\] => HYT00
            \[SQLSTATE\] => HYT00
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired
        \)

\)

