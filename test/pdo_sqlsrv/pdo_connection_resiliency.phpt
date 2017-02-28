--TEST--
Test the connection resiliency keywords ConnectRetryCount and ConnectRetryInterval and their ranges of acceptable values
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

$connectionInfo = "ConnectRetryCount=10; ConnectRetryInterval=30;";

try
{  
    $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName ; $connectionInfo", "$uid", "$pwd");
    //echo "Connected successfully on first attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    //echo "Could not connect on first attempt.\n";
    //print_r( $e->getMessage() );
    //echo "\n";
}

$connectionInfo = "ConnectRetryCount=0; ConnectRetryInterval=30;";
                         
try
{  
    $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName ; $connectionInfo", "$uid", "$pwd");
    //echo "Connected successfully on second attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    //echo "Could not connect on second attempt.\n";
    //print_r( $e->getMessage() );
    //echo "\n";
}

$connectionInfo = "ConnectRetryCount=256; ConnectRetryInterval=30;";
  
try
{  
    $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName ; $connectionInfo", "$uid", "$pwd");
    //echo "Connected successfully on third attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    //echo "Could not connect on third attempt.\n";
    //print_r( $e->getMessage() );
    //echo "\n";
}

$connectionInfo = "ConnectRetryCount=5; ConnectRetryInterval=70;";
                         
try
{  
    $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName ; $connectionInfo", "$uid", "$pwd");
    //echo "Connected successfully on fourth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    //echo "Could not connect on fourth attempt.\n";
    //print_r( $e->getMessage() );
    //echo "\n";
}

$connectionInfo = "ConnectRetryCount=-1; ConnectRetryInterval=30;";
                         
try
{  
    $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName ; $connectionInfo", "$uid", "$pwd");
    //echo "Connected successfully on fifth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    //echo "Could not connect on fifth attempt.\n";
    //print_r( $e->getMessage() );
    //echo "\n";
}

$connectionInfo = "ConnectRetryCount=thisisnotaninteger; ConnectRetryInterval=30;";
                         
try
{  
    $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName ; $connectionInfo", "$uid", "$pwd");
    //echo "Connected successfully on sixth attempt.\n";
    $conn = null;
}
catch( PDOException $e )
{
    //echo "Could not connect on sixth attempt.\n";
    //print_r( $e->getMessage() );
    //echo "\n";
}
?>
--EXPECT--
