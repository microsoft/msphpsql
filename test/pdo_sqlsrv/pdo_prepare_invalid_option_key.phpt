--TEST--
Test PDO::prepare by passing in a string key
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

try 
{   
    $databaseName = "tempdb";
    $dsn = "sqlsrv:Server = $server; database = $databaseName";
    $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    $conn = new PDO( $dsn, $uid, $pwd, $attr); 
    
    $stmt = $conn->prepare( "SELECT 1", array( "PDO::ATTR_CURSOR" => PDO::CURSOR_FWDONLY ));

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
  int(-43)
  [2]=>
  string(42) "An invalid statement option was specified."
}