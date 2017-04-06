--TEST--
Test setting invalid value or key i connection attributes 
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

try{
$database = "tempdb";
$dsn = 	"sqlsrv:Server = $serverName; Database = $database";

$conn = new PDO( $dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT)); 

// Negative value for query timeout: should raise error
@$conn->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, -1 );
print_r (($conn->errorInfo())[2]);
echo "\n";

// PDO::ATTR_CURSOR is a Statement Level Attribute only
@$conn->setAttribute( PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL );
print_r (($conn->errorInfo())[2]);
}
catch ( PDOException $e ){
    exit;
}

?> 

--EXPECT--

Invalid value -1 specified for option PDO::SQLSRV_ATTR_QUERY_TIMEOUT.
The given attribute is only supported on the PDOStatement object.