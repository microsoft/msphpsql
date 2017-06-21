--TEST--
indicate if the result set has one or more rows.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);

$stmt = sqlsrv_query( $conn, "select * from Person.Person where PersonType = 'EM'" , array());

if ($stmt !== NULL) {
  $rows = sqlsrv_has_rows( $stmt );

  if ($rows === true)
	 echo "\nthere are rows\n";
  else 
	 echo "\nno rows\n";
}
   
?>
--EXPECT--
there are rows