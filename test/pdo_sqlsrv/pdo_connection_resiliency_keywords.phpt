--TEST--
Test the connection resiliency keywords ConnectRetryCount and ConnectRetryInterval and their ranges of acceptable values
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 30;";

try
{
    $conn = new PDO( "sqlsrv:server = $server  ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on first attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on first attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount = 0; ConnectRetryInterval = 30;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on second attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on second attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount = 256; ConnectRetryInterval = 30;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on third attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on third attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount = 5; ConnectRetryInterval = 70;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on fourth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on fourth attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount = -1; ConnectRetryInterval = 30;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on fifth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on fifth attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount = thisisnotaninteger; ConnectRetryInterval = 30;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on sixth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on sixth attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount = 5; ConnectRetryInterval = 3.14159;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on seventh attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on seventh attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryCount;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on eighth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on eighth attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$connectionInfo = "ConnectRetryInterval;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ; $connectionInfo", "$uid", "$pwd" );
    echo "Connected successfully on ninth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect on ninth attempt.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

?>
--EXPECTREGEX--
Connected successfully on first attempt.
Connected successfully on second attempt.
Could not connect on third attempt.
SQLSTATE\[08001\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
Could not connect on fourth attempt.
SQLSTATE\[08001\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryInterval'
Could not connect on fifth attempt.
SQLSTATE\[08001\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
Could not connect on sixth attempt.
SQLSTATE\[08001\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryCount'
Could not connect on seventh attempt.
SQLSTATE\[08001\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ConnectRetryInterval'
Could not connect on eighth attempt.
SQLSTATE\[IMSSP\]: The DSN string ended unexpectedly.
Could not connect on ninth attempt.
SQLSTATE\[IMSSP\]: The DSN string ended unexpectedly.