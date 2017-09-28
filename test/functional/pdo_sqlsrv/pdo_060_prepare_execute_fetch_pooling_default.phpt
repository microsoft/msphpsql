--TEST--
Prepare, execute statement and fetch with pooling unset (default)
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );

// Allow PHP types for numeric fields
$connection_options = array( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => TRUE );

// Create a pool
$conn0 = connect( '', $connection_options );
unset( $conn0 );

// Connection can use an existing pool
$conn = connect( '', $connection_options );
    
// Create table
$tableName = 'pdo_060_test';
create_table( $conn, $tableName, array( new columnMeta( "nvarchar(32)", "Столица" ), new columnMeta( "int", "year" )));

// Insert data
insert_row( $conn, $tableName, array( "Столица" => "Лондон", "year" => 2012 ), "prepareExecuteBind" );

// Get data
$row = select_row( $conn, $tableName, "PDO::FETCH_ASSOC" );
var_dump($row);  
unset( $conn );

// Create a new pool
$conn0 = connect();
unset( $conn0 );
    
// Connection can use an existing pool
$conn = connect();

// Get data
$row = select_row( $conn, $tableName, "PDO::FETCH_ASSOC" );
var_dump($row); 
    
// Close connection
DropTable( $conn, $tableName );
unset( $stmt );
unset( $conn );
print "Done"
?>
--EXPECT--
array(2) {
  ["Столица"]=>
  string(12) "Лондон"
  ["year"]=>
  int(2012)
}
array(2) {
  ["Столица"]=>
  string(12) "Лондон"
  ["year"]=>
  string(4) "2012"
}
Done
