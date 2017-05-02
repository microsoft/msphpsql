--TEST--
Prepare, execute statement and fetch with pooling unset (default)
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

// Allow PHP types for numeric fields
$connection_options['pdo'][PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE] = TRUE;

// Create a pool
$conn0 = new PDO( "sqlsrv:server=$server;database=$databaseName;",
    $uid, $pwd, $connection_options['pdo']);
$conn0 = null;

// Connection can use an existing pool
$conn = new PDO( "sqlsrv:server=$server;database=$databaseName;",
    $uid, $pwd, $connection_options['pdo']);

// Create table
$tableName = 'pdo_060_test';
$sql = "CREATE TABLE $tableName (Столица NVARCHAR(32), year INT)";
$stmt = $conn->query($sql);

// Insert data
$sql = "INSERT INTO $tableName VALUES (?,?)";
$stmt = $conn->prepare($sql);
$stmt->execute(array("Лондон",2012));

// Get data
$stmt = $conn->query("SELECT * FROM $tableName");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row);  
$conn = null;

// Create a new pool
$conn0 = new PDO( "sqlsrv:server=$server;database=$databaseName;",
    $uid, $pwd);
$conn0 = null;
    
// Connection can use an existing pool
$conn = new PDO( "sqlsrv:server=$server;database=$databaseName;",
    $uid, $pwd);

// Get data
$stmt = $conn->query("SELECT * FROM $tableName");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row); 
    
    
$conn->query("DROP TABLE $tableName");
    
// Close connection
$stmt=null;
$conn=null;
print "Done"
?>
--EXPECT--
array(2) {
  ["Столица"]=>
  string(12) "Лондон"
  ["year"]=>
  int(2012)
}
array(2) {
  ["Столица"]=>
  string(12) "Лондон"
  ["year"]=>
  string(4) "2012"
}
Done
