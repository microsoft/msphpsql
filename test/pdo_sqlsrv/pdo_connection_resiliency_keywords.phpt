--TEST--
Test the connection resiliency keywords ConnectRetryCount and ConnectRetryInterval and their ranges of acceptable values
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

function TryToConnect( $server, $uid, $pwd, $retryCount, $retryInterval, $number )
{
    $connectionInfo = "ConnectRetryCount = $retryCount; ConnectRetryInterval = $retryInterval;";

    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
        echo "Connected successfully on $number attempt.\n";
        $conn = null;
    }
    catch( PDOException $e )
    {
        echo "Could not connect on $number attempt.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }
}

TryToConnect( $server, $uid, $pwd,  10, 30, 'first');
TryToConnect( $server, $uid, $pwd,   0, 30, 'second');
TryToConnect( $server, $uid, $pwd, 256, 30, 'third');
TryToConnect( $server, $uid, $pwd,   5, 70, 'fourth');
TryToConnect( $server, $uid, $pwd,  -1, 30, 'fifth');
TryToConnect( $server, $uid, $pwd, 'thisisnotaninteger', 30, 'sixth');
TryToConnect( $server, $uid, $pwd,   5, 3.14159, 'seventh');

$connectionInfo = "ConnectRetryCount;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
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
    $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
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