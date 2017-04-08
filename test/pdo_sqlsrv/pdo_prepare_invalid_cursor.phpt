--TEST--
Test PDO::prepare by passing in invalid cursor value
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
    $database = "tempdb";
    $conn = new PDO( "sqlsrv:Server = $serverName; Database = $database", $username, $password); 
    //$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    
    // PDO::CURSOR_FWDONLY should not be quoted
    $stmt1 = $conn->prepare( "SELECT 1", array( PDO::ATTR_CURSOR => "PDO::CURSOR_FWDONLY" ));
    
    // 10 is an invalid value for PDO::ATTR_CURSOR
    $stmt2 = $conn->prepare( "SELECT 2", array( PDO::ATTR_CURSOR => 10 ));

    if ( $stmt1 || $stmt2 )
    {
        echo "Invalid values for PDO::ATTR_CURSOR should return false.\n";
    } else {
        echo "Invalid values for PDO::ATTR_CURSOR return false.\n";
    }
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
}
?> 

--EXPECT--

Invalid values for PDO::ATTR_CURSOR return false.