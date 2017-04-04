--TEST--
Test getting invalid attributes
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try{
$database = "tempdb";
$dsn = 	"sqlsrv:Server = $serverName; Database = $database";

$conn = new PDO( $dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT)); 
   
@$conn->getAttribute( PDO::ATTR_FETCH_TABLE_NAMES );
print_r (($conn->errorInfo())[2]);
echo "\n";

@$conn->getAttribute( PDO::ATTR_CURSOR );
print_r (($conn->errorInfo())[2]);
echo "\n";

@$conn->getAttribute( PDO::ATTR_CONNECTION_STATUS );
print_r (($conn->errorInfo())[2]);
echo "\n";
}
catch ( PDOException $e ){
    exit;
}

?> 

--EXPECT--

An unsupported attribute was designated on the PDO object.
The given attribute is only supported on the PDOStatement object.
An invalid attribute was designated on the PDO object.