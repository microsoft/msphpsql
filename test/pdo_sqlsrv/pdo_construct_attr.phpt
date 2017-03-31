--TEST--
Test PDO::__Construct by passing connection options
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
	$attr = array(
		PDO::SQLSRV_ATTR_ENCODING => 3, 
		PDO::ATTR_CASE => 2,
		PDO::ATTR_PREFETCH => false,
		PDO::ATTR_TIMEOUT => 35,		
		PDO::ATTR_STRINGIFY_FETCHES => true,
		PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE => -1,
		PDO::SQLSRV_ATTR_DIRECT_QUERY => true		
	); 
    
    $database = "tempdb";
	$dsn = 	"sqlsrv:Server = $serverName; Database = $database";
    
    $conn = new PDO( $dsn, $username, $password, $attr); 
    
    $stmt = $conn->prepare("SELECT 1");
    $stmt->execute();
    
    $stmt = NULL;
    $conn = NULL;

  
  echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}
?> 

--EXPECT--

Test Successful