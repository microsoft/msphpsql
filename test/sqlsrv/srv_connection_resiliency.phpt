--TEST--
Test the connection resiliency keywords ConnectRetryCount and ConnectRetryInterval and their ranges of acceptable values
--SKIPIF--
<?php if (!(strtoupper(substr(php_uname('s'),0,3)) === 'WIN')) die("Skip, not running on windows."); ?>
--FILE--
<?php
require_once("autonomous_setup.php");

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 
                         "ConnectRetryCount"=>10, "ConnectRetryInterval"=>30 );

$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
    echo "Could not connect on first attempt.\n";
    print_r( sqlsrv_errors());
}
else
{
    echo "Connected successfully on first attempt.\n";
    sqlsrv_close( $conn);
}

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 
                         "ConnectRetryCount"=>0, "ConnectRetryInterval"=>30 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
    echo "Could not connect on second attempt.\n";
    print_r( sqlsrv_errors());
}
else
{
    echo "Connected successfully on second attempt.\n";
    sqlsrv_close( $conn);
}

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 
                         "ConnectRetryCount"=>256, "ConnectRetryInterval"=>30 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
    echo "Could not connect on third attempt.\n";
    print_r( sqlsrv_errors());
}
else
{
    echo "Connected successfully on third attempt.\n";
    sqlsrv_close( $conn);
}

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 
                         "ConnectRetryCount"=>5, "ConnectRetryInterval"=>70 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
    echo "Could not connect on fourth attempt.\n";
    print_r( sqlsrv_errors());
}
else
{
    echo "Connected successfully on fourth attempt.\n";
    sqlsrv_close( $conn);
}

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 
                         "ConnectRetryCount"=>-1, "ConnectRetryInterval"=>30 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
    echo "Could not connect on fifth attempt.\n";
    print_r( sqlsrv_errors());
}
else
{
    echo "Connected successfully on fifth attempt.\n";
    sqlsrv_close( $conn);
}

$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 
                         "ConnectRetryCount"=>"thisisnotaninteger", "ConnectRetryInterval"=>30 );
                         
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
    echo "Could not connect on sixth attempt.\n";
    print_r( sqlsrv_errors());
}
else
{
    echo "Connected successfully on sixth attempt.\n";
    sqlsrv_close( $conn);
}
?>
--EXPECT--
Connected successfully on first attempt.
Connected successfully on second attempt.
Could not connect on third attempt.
Array
(
    [0] => Array
        (
            [0] => 08001
            [SQLSTATE] => 08001
            [1] => 0
            [code] => 0
            [2] => [Microsoft][ODBC Driver 13 for SQL Server]Invalid value specified for connection string attribute 'ConnectRetryCount'
            [message] => [Microsoft][ODBC Driver 13 for SQL Server]Invalid value specified for connection string attribute 'ConnectRetryCount'
        )

)
Could not connect on fourth attempt.
Array
(
    [0] => Array
        (
            [0] => 08001
            [SQLSTATE] => 08001
            [1] => 0
            [code] => 0
            [2] => [Microsoft][ODBC Driver 13 for SQL Server]Invalid value specified for connection string attribute 'ConnectRetryInterval'
            [message] => [Microsoft][ODBC Driver 13 for SQL Server]Invalid value specified for connection string attribute 'ConnectRetryInterval'
        )

)
Could not connect on fifth attempt.
Array
(
    [0] => Array
        (
            [0] => 08001
            [SQLSTATE] => 08001
            [1] => 0
            [code] => 0
            [2] => [Microsoft][ODBC Driver 13 for SQL Server]Invalid value specified for connection string attribute 'ConnectRetryCount'
            [message] => [Microsoft][ODBC Driver 13 for SQL Server]Invalid value specified for connection string attribute 'ConnectRetryCount'
        )

)
Could not connect on sixth attempt.
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -33
            [code] => -33
            [2] => Invalid value type for option ConnectRetryCount was specified.  Integer type was expected.
            [message] => Invalid value type for option ConnectRetryCount was specified.  Integer type was expected.
        )

)