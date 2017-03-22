--TEST--
Test the connection resiliency keywords ConnectRetryCount and ConnectRetryInterval and their ranges of acceptable values
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break.php" );

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>30 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on first attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on first attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>0, "ConnectRetryInterval"=>30 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on second attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on second attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>256, "ConnectRetryInterval"=>30 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on third attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on third attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>5, "ConnectRetryInterval"=>70 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on fourth attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on fourth attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>-1, "ConnectRetryInterval"=>30 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on fifth attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on fifth attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>"thisisnotaninteger", "ConnectRetryInterval"=>30 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on sixth attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on sixth attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount"=>5, "ConnectRetryInterval"=>3.14159 );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on seventh attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on seventh attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryCount" );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on eighth attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on eighth attempt.\n";
    sqlsrv_close( $conn );
}

$connectionInfo = array( "Database"=>"$databaseName", "uid"=>"$username", "pwd"=>"$password",
                         "ConnectRetryInterval" );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect on ninth attempt.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully on ninth attempt.\n";
    sqlsrv_close( $conn );
}

?>
--EXPECTREGEX--
Connected successfully on first attempt.
Connected successfully on second attempt.
Could not connect on third attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08001
            \[SQLSTATE\] => 08001
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
        \)

\)
Could not connect on fourth attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08001
            \[SQLSTATE\] => 08001
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryInterval'
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryInterval'
        \)

\)
Could not connect on fifth attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08001
            \[SQLSTATE\] => 08001
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
        \)

\)
Could not connect on sixth attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -33
            \[code\] => -33
            \[2\] => Invalid value type for option ConnectRetryCount was specified.  Integer type was expected.
            \[message\] => Invalid value type for option ConnectRetryCount was specified.  Integer type was expected.
        \)

\)
Could not connect on seventh attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -33
            \[code\] => -33
            \[2\] => Invalid value type for option ConnectRetryInterval was specified.  Integer type was expected.
            \[message\] => Invalid value type for option ConnectRetryInterval was specified.  Integer type was expected.
        \)

\)
Could not connect on eighth attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -8
            \[code\] => -8
            \[2\] => An invalid connection option key type was received. Option key types must be strings.
            \[message\] => An invalid connection option key type was received. Option key types must be strings.
        \)

\)
Could not connect on ninth attempt.
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -8
            \[code\] => -8
            \[2\] => An invalid connection option key type was received. Option key types must be strings.
            \[message\] => An invalid connection option key type was received. Option key types must be strings.
        \)

\)