--TEST--
Insert with quoted parameters
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

// Connect
$conn = connect();

$param = 'a \' g';
$param2 = $conn->quote( $param );

// Create a temporary table
$tableName = GetTempTableName( '', false );
$stmt = create_table( $conn, $tableName, array( new columnMeta( "varchar(10)", "col1" ), new columnMeta( "varchar(20)", "col2" )));
if( $stmt === false ) { die(); }

// Insert data
if ( !is_col_encrypted() )
{
    $query = "INSERT INTO $tableName VALUES( ?, '1' )";
    $stmt = $conn->prepare( $query );
    $stmt->execute(array($param));
}
else
{
    insert_row( $conn, $tableName, array( "col1" => $param, "col2" => "1" ), "prepareExecuteBind" );
}

// Insert data
    insert_row( $conn, $tableName, array( "col1" => $param, "col2" => $param2 ), "prepareExecuteBind" );

// Query
$query = "SELECT * FROM $tableName";
$stmt = $conn->query($query);
while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print_r( $row['col1'] ." was inserted\n" );
}

// Revert the inserts
$query = "delete from $tableName where col1 = ?";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

//free the statement and connection
DropTable( $conn, $tableName );
unset( $stmt );
unset( $conn );
?>
--EXPECT--
a ' g was inserted
a ' g was inserted