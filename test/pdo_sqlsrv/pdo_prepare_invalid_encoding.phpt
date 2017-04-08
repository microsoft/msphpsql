--TEST--
Test PDO::prepare by passing in invalid encoding values
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
    $database = "tempdb";
    $conn = new PDO( "sqlsrv:Server = $serverName; Database = $database", $username, $password); 
    //$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    
    // PDO::SQLSRV_ENCODING_SYSTEM should not be quoted
    $stmt1 = $conn->prepare( "SELECT 1", array( PDO::SQLSRV_ATTR_ENCODING => "PDO::SQLSRV_ENCODING_SYSTEM" ));
    
    // 10 is an invalid value for PDO::SQLSRV_ATTR_ENCODING
    $stmt2 = $conn->prepare( "SELECT 2", array( PDO::SQLSRV_ATTR_ENCODING => 10 ));

    if ( $stmt1 || $stmt2 )
    {
        echo "Invalid values for PDO::SQLSRV_ATTR_ENCODING should return false.\n";
    } else {
        echo "Invalid values for PDO::SQLSRV_ATTR_ENCODING return false.\n";
    }
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
}
?> 

--EXPECT--

Invalid values for PDO::SQLSRV_ATTR_ENCODING return false.