--TEST--
Test PDO::__Construct by passing connection options
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
 
require_once("MsSetup.inc");

try 
{   
    $dsn = "sqlsrv:Server = $server;" .
           "ConnectionPooling = false;" .
           "APP = whatever;" .
           "LoginTimeout = 1;" .
           "ApplicationIntent = ReadOnly;" .
           "database = $databaseName;" .
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
    $conn = new PDO( $dsn, $uid, $pwd); 

    echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}
?> 

--EXPECT--

Test Successful