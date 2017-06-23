--TEST--
Test PDO::__Construct by passing connection options and attributes.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsSetup.inc';

try 
{   
    $attr = array(
        PDO::SQLSRV_ATTR_ENCODING => 3, 
        PDO::ATTR_CASE => 2,
        PDO::ATTR_PREFETCH => false,
        PDO::ATTR_TIMEOUT => 35,        
        PDO::ATTR_ERRMODE => 2,         
        PDO::ATTR_STRINGIFY_FETCHES => true,
        PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE => 5120,
        PDO::SQLSRV_ATTR_DIRECT_QUERY => true       
    ); 
    
    $dsn =  "sqlsrv:Server = $server;" .
            "ConnectionPooling = false;" .
            "APP = whatever;" .
            "LoginTimeout = 1;" .
            "ApplicationIntent = ReadOnly;" .
            "Database = $databaseName;" .
            "Encrypt = false;" .
            "Failover_Partner = whatever;" .
            "MultipleActiveResultSets = true;" .
            "MultiSubnetFailover = NO;" .
            "QuotedId = false;" .
            "TraceFile = whatever;" .
            "TraceOn = true;" .
            "TransactionIsolation = " . PDO::SQLSRV_TXN_READ_UNCOMMITTED . ";" .
            "TrustServerCertificate = false;" .
            "WSID = whatever;"
            ;
    $conn = new PDO( $dsn, $uid, $pwd, $attr); 

    echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}
?> 

--EXPECT--

Test Successful
