--TEST--
Test PDO::__Construct by passing connection options
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
    $database = "tempdb";
	$dsn = 	"sqlsrv:Server = $serverName;" .
			"Database = $database;" .
			"InvalidKey = true;"
			;
    $conn = new PDO( $dsn, $username, $password); 

  
  echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
    exit;
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