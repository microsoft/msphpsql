--TEST--
Test new connection keyword ColumnEncryption
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
$msodbcsql_maj = "";

try
{
    $conn = new PDO( "sqlsrv:server = $server", $uid, $pwd );
    $msodbcsql_ver = $conn->getAttribute( PDO::ATTR_CLIENT_VERSION )['DriverVer'];
    $msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
}
catch( PDOException $e )
{
    echo "Failed to connect\n";
    print_r( $e->getMessage() );
    echo "\n";
}

test_ColumnEncryption( $server, $uid, $pwd, $msodbcsql_maj );
echo "Done";


function verify_output( $PDOerror, $expected )
{
    if( strpos( $PDOerror->getMessage(), $expected ) === false )
    {
        print_r( $PDOerror->getMessage() );
        echo "\n";
    }
}

function test_ColumnEncryption( $server, $uid, $pwd, $msodbcsql_maj )
{
    // Only works for ODBC 17
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = Enabled;";
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        if($msodbcsql_maj < 17)
        {
            $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
            verify_output( $e, $expected );
        }
        else
        {
            print_r( $e->getMessage() );
            echo "\n";
        }
    }

    // Works for ODBC 17, ODBC 13
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = Disabled;";
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        if($msodbcsql_maj < 13)
        {
            $expected = "Invalid connection string attribute";
            verify_output( $e, $expected );
        }
        else
        {
            print_r( $e->getMessage() );
            echo "\n";
        }
    }

    // should fail for all ODBC drivers
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = false;";
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        $expected = "Invalid value specified for connection string attribute 'ColumnEncryption'";
        verify_output( $e, $expected );   
    }

    // should fail for all ODBC drivers
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = 1;";
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        $expected = "Invalid value specified for connection string attribute 'ColumnEncryption'";
        verify_output( $e, $expected );
    }	
}     
?>
--EXPECT--
Done