--TEST--
Test PDO::prepare by passing in a string key
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
    $database = "tempdb";
	$dsn = 	"sqlsrv:Server = $serverName; Database = $database";
    $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    $conn = new PDO( $dsn, $username, $password, $attr); 
    
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