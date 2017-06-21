--TEST--
accesses an output parameter
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$input1 = 'bb';

$stmt = $conn->prepare("select ? = count(* ) from Person.Person");
$stmt->bindParam( 1, $input1, PDO::PARAM_STR, 10);
$stmt->execute();
echo "Result: ".$input1;

//free the statement and connection
$conn = null;
$stmt = null;
?>
--EXPECT--
Result: 19972