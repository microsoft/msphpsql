--TEST--
retrieves metadata for a column
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$stmt = $conn->query("select * from Person.ContactType");
$metadata = $stmt->getColumnMeta(2);
var_dump($metadata);

print $metadata['sqlsrv:decl_type'] . "\n";
print $metadata['native_type'] . "\n";
print $metadata['name'];

// free the statement and connection
$stmt = null;
$conn = null;
?>
--EXPECT--
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(8) "datetime"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(12) "ModifiedDate"
  ["len"]=>
  int(23)
  ["precision"]=>
  int(3)
}
datetime
string
ModifiedDate