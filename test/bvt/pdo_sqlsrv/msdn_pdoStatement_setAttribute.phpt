--TEST--
sets the query timeout attribute
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd", array('MultipleActiveResultSets'=>false )  );

$stmt = $conn->prepare('SELECT * FROM Person.ContactType');

echo "Attribute number for ATTR_CURSOR: ".$stmt->getAttribute( constant( "PDO::ATTR_CURSOR" ) );

echo "\n";

$stmt->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 2);
echo "Attribute number for SQLSRV_ATTR_QUERY_TIMEOUT: ".$stmt->getAttribute( constant( "PDO::SQLSRV_ATTR_QUERY_TIMEOUT" ) );

//free the statement and connection
$stmt = null;
$conn = null;
?>
--EXPECT--
Attribute number for ATTR_CURSOR: 0
Attribute number for SQLSRV_ATTR_QUERY_TIMEOUT: 2