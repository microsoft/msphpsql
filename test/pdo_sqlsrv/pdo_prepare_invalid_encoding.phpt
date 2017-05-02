--TEST--
Test PDO::prepare by passing in invalid encoding values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once("MsSetup.inc");

try 
{   
    $databaseName = "tempdb";
    $conn = new PDO( "sqlsrv:Server = $server; database = $databaseName", $uid, $pwd); 
    
    // PDO::SQLSRV_ENCODING_SYSTEM should not be quoted
    $stmt1 = $conn->prepare( "SELECT 1", array( PDO::SQLSRV_ATTR_ENCODING => "PDO::SQLSRV_ENCODING_SYSTEM" ));
    print_r(($conn->errorInfo())[2]);
    echo "\n";
    
    // 10 is an invalid value for PDO::SQLSRV_ATTR_ENCODING
    $stmt2 = $conn->prepare( "SELECT 2", array( PDO::SQLSRV_ATTR_ENCODING => 10 ));
    print_r(($conn->errorInfo())[2]);
    echo "\n";
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
}
?> 

--EXPECT--

An invalid encoding was specified for SQLSRV_ATTR_ENCODING.
An invalid encoding was specified for SQLSRV_ATTR_ENCODING.