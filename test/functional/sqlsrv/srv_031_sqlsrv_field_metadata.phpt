--TEST--
sqlsrv_field_metadata() VARCHAR(n), NVARCHAR(n), INT
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect();
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

$tableName = 'test_srv_031';

// Create table
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (FirstName VARCHAR(10), LastName NVARCHAR(20), Age INT)");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

// Insert data
$sql = "INSERT INTO $tableName VALUES ('John', 'Doe', 30)";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_free_stmt($stmt);

// Insert data
$sql = "INSERT INTO $tableName VALUES ('Nhoj', N'Eoduard', -3),('Vi Lo', N'N/A', 1987)";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_free_stmt($stmt);

// Prepare the statement
$query = "SELECT FirstName, LastName, Age FROM $tableName";
$stmt = sqlsrv_prepare($conn, $query);

// Get field metadata
$metadata = sqlsrv_field_metadata($stmt);
if (! $metadata) {
    printErrors();
} else {
    var_dump($metadata);
}

sqlsrv_query($conn, "DROP TABLE $tableName");

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
array(3) {
  [0]=>
  array(6) {
    ["Name"]=>
    string(9) "FirstName"
    ["Type"]=>
    int(12)
    ["Size"]=>
    int(10)
    ["Precision"]=>
    NULL
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(1)
  }
  [1]=>
  array(6) {
    ["Name"]=>
    string(8) "LastName"
    ["Type"]=>
    int(-9)
    ["Size"]=>
    int(20)
    ["Precision"]=>
    NULL
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(1)
  }
  [2]=>
  array(6) {
    ["Name"]=>
    string(3) "Age"
    ["Type"]=>
    int(4)
    ["Size"]=>
    NULL
    ["Precision"]=>
    int(10)
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(1)
  }
}
Done
