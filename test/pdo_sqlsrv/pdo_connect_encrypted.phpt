--TEST--
Test new connection keyword ColumnEncryption
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require_once("MsSetup.inc");

    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled;";

    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
        echo "Connected successfully with ColumnEncryption enabled.\n";
    }
    catch( PDOException $e )
    {
        echo "Failed to connect with ColumnEncryption enabled.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }

    $conn = null;

    ////////////////////////////////////////
    $connectionInfo = "Database = $databaseName; ColumnEncryption = false;";
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        echo "Failed to connect.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }

    ////////////////////////////////////////
    $connectionInfo = "Database = $databaseName; ColumnEncryption = 1;";
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        echo "Failed to connect.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }
       
    ////////////////////////////////////////
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Disabled;";

    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
        echo "Connected successfully with ColumnEncryption disabled.\n";
    }
    catch( PDOException $e )
    {
        echo "Failed to connect with ColumnEncryption disabled.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }

    $conn = null;

    echo "Done\n";

?>
--EXPECTREGEX--
Connected successfully with ColumnEncryption enabled.
Failed to connect.
SQLSTATE\[08001\]: .*\[Microsoft\]\[ODBC Driver 13 for SQL Server\]Invalid value specified for connection string attribute 'ColumnEncryption'
Failed to connect.
SQLSTATE\[08001\]: .*\[Microsoft\]\[ODBC Driver 13 for SQL Server\]Invalid value specified for connection string attribute 'ColumnEncryption'
Connected successfully with ColumnEncryption disabled.
Done
