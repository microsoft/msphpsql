--TEST--
Bind integer parameters; allow fetch numeric types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

/* Sample numbers MIN_INT, MAX_INT */
$sample = array(-2**31, 2**31-1);

/* Connect */
$conn = connect( '', array( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => TRUE ));

// Create table
$tableName = 'testPDO016';
create_table( $conn, $tableName, array( new ColumnMeta( "int", "c1" ), new ColumnMeta( "int", "c2" )));

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (:num1, :num2)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':num1', $sample[0], PDO::PARAM_INT);
$stmt->bindParam(':num2', $sample[1], PDO::PARAM_INT);
$stmt->execute();

// Fetch, get data
$sql = "SELECT * FROM $tableName";
$stmt = $conn->query($sql);
$row = $stmt->fetch(PDO::FETCH_NUM);
var_dump ($row);

// Close connection
DropTable( $conn, $tableName );
unset( $stmt );
unset( $conn );

print "Done";
?>

--EXPECT--
array(2) {
  [0]=>
  int(-2147483648)
  [1]=>
  int(2147483647)
}
Done
