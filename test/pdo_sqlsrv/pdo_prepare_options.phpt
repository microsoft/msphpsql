--TEST--
Test PDO::prepare by passing in attributes
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   

    class CustomPDOStatement extends PDOStatement
    {
        protected function __construct() {
        }
    }

    $database = "tempdb";
	$dsn = 	"sqlsrv:Server = $serverName; Database = $database";
    $prep_attr = array(PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                       PDO::ATTR_STATEMENT_CLASS => array('CustomPDOStatement', array()),
                       PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
                       PDO::ATTR_EMULATE_PREPARES => false,
                       PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true
                       );
    $conn = new PDO( $dsn, $username, $password); 
    
    $stmt = $conn->prepare( "SELECT 1", $prep_attr );

  
  echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
    exit;
}
?> 

--EXPECT--

Test Successful