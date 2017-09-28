--TEST--
Bind parameters VARCHAR(n) extended ASCII
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );

// Connect
$conn = connect();

// Create table
$tableName = 'extendedAscii';
create_table( $conn, $tableName, array( new columnMeta( "char(2)", "code" ), new columnMeta( "varchar(32)", "city" )));

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (?,?)";

// First row 
$stmt = $conn->prepare($sql);
$params = array("FI","Järvenpää");
$stmt->execute($params);

// Second row
$params = array("DE","München");
$stmt->execute($params);

// Query, fetch
$data = select_all( $conn, $tableName );

// Print out
foreach ($data as $a)
echo $a[0] . "|" . $a[1] . "\n";

// Close connection
DropTable( $conn, $tableName );
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
FI|Järvenpää
DE|München
Done
