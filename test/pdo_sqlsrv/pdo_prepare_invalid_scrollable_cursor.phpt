--TEST--
Test PDO::prepare by passing in invalid scrollable type value
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try 
{   
    $database = "tempdb";
    $conn = new PDO( "sqlsrv:Server = $serverName; Database = $database", $username, $password); 
    //$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    
    // PDO::SQLSRV_CURSOR_BUFFERED should not be quoted
    $stmt1 = $conn->prepare( "SELECT 1", array( PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => "PDO::SQLSRV_CURSOR_BUFFERED" ));
    
    // if ATTR_CURSOR is FWDONLY, cannot set SCROLL_TYPE
    $stmt2 = $conn->prepare( "SELECT 2", array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED ));

    if ( $stmt1 || $stmt2 )
    {
        echo "Invalid values for PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE should return false.\n";
    } else {
        echo "Invalid values for PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE return false.\n";
    }
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
}
?> 

--EXPECT--

Invalid values for PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE return false.