--TEST--
Enable multiple active result sets (MARS)
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect(array('MultipleActiveResultSets' => true));
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

// Query
$stmt1 = sqlsrv_query($conn, "SELECT 'ONE'") ?: die(print_r(sqlsrv_errors(), true));
sqlsrv_fetch($stmt1);

// Query. Returns if multiple result sets are disabled
$stmt2 = sqlsrv_query($conn, "SELECT 'TWO'") ?: die(print_r(sqlsrv_errors(), true));
sqlsrv_fetch($stmt2);

// Print the data
$res = [ sqlsrv_get_field($stmt1, 0), sqlsrv_get_field($stmt2, 0) ];
var_dump($res);

// Free statement and connection resources
sqlsrv_free_stmt($stmt1);
sqlsrv_free_stmt($stmt2);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
array(2) {
  [0]=>
  string(3) "ONE"
  [1]=>
  string(3) "TWO"
}
Done
