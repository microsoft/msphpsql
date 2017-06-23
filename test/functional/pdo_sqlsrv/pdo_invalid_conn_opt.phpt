--TEST--
Test PDO::__Construct with invalid connection option
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once("MsSetup.inc");

try 
{   
    $dsn = "sqlsrv:Server = $server;" .
           "database = $databaseName;" .
           "InvalidKey = true;"
           ;
    $conn = new PDO( $dsn, $uid, $pwd); 

    echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
}
?> 

--EXPECT--

array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-42)
  [2]=>
  string(64) "An invalid keyword 'InvalidKey' was specified in the DSN string."
}