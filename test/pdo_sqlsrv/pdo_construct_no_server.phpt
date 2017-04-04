--TEST--
Test PDO::__Construct without specifying the Server
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");
try 
{   
    $database = "tempdb";
    // Try to connect with no server specific
    @$conn = new PDO( "sqlsrv:Database = $database", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
}
?> 

--EXPECT--

Server keyword was not specified in the DSN string.