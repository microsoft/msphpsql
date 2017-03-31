--TEST--
Test the connection resiliency keywords ConnectRetryCount and ConnectRetryInterval and their ranges of acceptable values
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "autonomous_setup.php" );

function TryToConnect( $server, $uid, $pwd, $retryCount, $retryInterval, $number )
{
    $connectionInfo = array( "UID"=>$uid, "PWD"=>$pwd,
                             "ConnectRetryCount"=>$retryCount, "ConnectRetryInterval"=>$retryInterval );

    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Could not connect on $number attempt.\n";
        print_r( sqlsrv_errors() );
    }
    else
    {
        echo "Connected successfully on $number attempt.\n";
     $stmt1 = sqlsrv_query( $conn, "SELECT @@SPID" );
      if ( sqlsrv_fetch( $stmt1 ) )
      {
          $spid=sqlsrv_get_field( $stmt1, 0 );
      }
       $stmt3 = sqlsrv_query( $conn, "SELECT * FROM sys.dm_exec_connections 
                                WHERE session_id = $spid");
        if ( sqlsrv_fetch( $stmt3 ) )
        {
            $prot=sqlsrv_get_field( $stmt3, 3 );
        }
        echo "prot = ".sqlsrv_fetch_array( $stmt3 )."\n";
        sqlsrv_close( $conn );
    }
}

TryToConnect( $server, $uid, $pwd,  10, 30, 'first');
TryToConnect( $server, $uid, $pwd,   0, 30, 'second');
TryToConnect( $server, $uid, $pwd, 256, 30, 'third');
TryToConnect( $server, $uid, $pwd,   5, 70, 'fourth');
TryToConnect( $server, $uid, $pwd,  -1, 30, 'fifth');
TryToConnect( $server, $uid, $pwd, 'thisisnotaninteger', 30, 'sixth');
TryToConnect( $server, $uid, $pwd,   5, 3.14159, 'seventh');

$connectionInfo = array( "UID"=>$uid, "PWD"=>$pwd, "ConnectRetryCount" );

$conn = sqlsrv_connect( $server, $connectionInfo );
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

$connectionInfo = array( "UID"=>$uid, "PWD"=>$pwd, "ConnectRetryInterval" );

$conn = sqlsrv_connect( $server, $connectionInfo );
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