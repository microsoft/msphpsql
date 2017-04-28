--TEST--
Test PDO::__Construct by passing connection options
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
    $database = "tempdb";
    $dsn = "sqlsrv:Server = $serverName;" .
           "ConnectionPooling = false;" .
           "APP = whatever;" .
           "LoginTimeout = 1;" .
           "ApplicationIntent = ReadOnly;" .
           "Database = $database;" .
           "Encrypt = false;" .
           "Failover_Partner = whatever;" .
           "MultipleActiveResultSets = true;" .
           "MultiSubnetFailover = NO;" .
           "QuotedId = false;" .
           "TraceFile = whatever;" .
           "TraceOn = true;" .
           "TrustServerCertificate = false;" .
           "WSID = whatever;"
           ;
    $conn = new PDO( $dsn, $username, $password); 

  
    echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}
?> 

--EXPECT--

Test Successful