--TEST--
Moves the cursor to the next result set with pooling enabled
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );

// Create a pool
$conn0 = connect( "ConnectionPooling=1" );
$conn0->query("SELECT 1");
$conn0 = null;

// Connect
$conn = connect( "ConnectionPooling=1" );

// Create table
$tableName = 'nextResultPooling';
create_table( $conn, $tableName, array( new ColumnMeta( "int", "c1" ), new ColumnMeta( "varchar(40)", "c2" )));

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (?,?)";
for($t=200; $t<220; $t++) {
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(1, $t);
	$ts = substr(sha1($t),0,5);
	$stmt->bindParam(2, $ts);
	$stmt->execute();
}

// Fetch, get data and move the cursor to the next result set
if ( !is_col_encrypted() )
{
    $sql = "SELECT * from $tableName WHERE c1 = '204' OR c1 = '210'; 
            SELECT Top 3 * FROM $tableName ORDER BY c1 DESC";
    $stmt = $conn->query($sql);
}
else
{
    $sql = "SELECT * FROM $tableName WHERE c1 = ? OR c1 = ?;
            SELECT Top 3 * FROM $tableName ORDER BY c1 DESC";
    $stmt = $conn->prepare( $sql );
    $stmt->execute( array( '204', '210' ));
}
$data1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset();
$data2 = $stmt->fetchAll(PDO::FETCH_NUM);

// Array: FETCH_ASSOC
foreach ($data1 as $a)
echo $a['c1']."|".$a['c2']."\n";

// Array: FETCH_NUM
foreach ($data2 as $a)
echo $a[0] . "|".$a[1]."\n";

// Close connection
DropTable( $conn, $tableName );
unset( $stmt );
unset( $conn );

print "Done";
?>

--EXPECT--
204|1cc64
210|135de
219|c0ba1
218|3d5bd
217|49e3d
Done
