--TEST--
Test PDO::prepare by passing in attributes
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once("MsSetup.inc");

try 
{   
    class CustomPDOStatement extends PDOStatement
    {
        protected function __construct() {
        }
    }

    $databaseName = "tempdb";
    $dsn = "sqlsrv:Server = $server; database = $databaseName";
    $prep_attr = array(PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                       PDO::ATTR_STATEMENT_CLASS => array('CustomPDOStatement', array()),
                       PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
                       PDO::ATTR_EMULATE_PREPARES => false,
                       PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true
                       );
    $conn = new PDO( $dsn, $uid, $pwd); 
    
    $stmt = $conn->prepare( "SELECT 1", $prep_attr );

    echo "Test Successful";
}
catch( PDOException $e ) {
    echo $e->getMessage();
}
?> 

--EXPECT--

Test Successful