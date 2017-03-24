--TEST--
Test connection resiliency timeouts
--DESCRIPTION--
1. Connect with ConnectRetryCount equal to 0.
2. Reconnect with the default value of ConnectRetryCount( 1 ).
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
                         "ConnectRetryCount"=>0 );
                         
$conn = sqlsrv_connect( $serverName, $connectionInfo );
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

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$username", "PWD"=>"$password",
                         "ConnectRetryInterval"=>10 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
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
