--TEST--
server side cursor specified when querying
--SKIPIF--

--FILE--
<?php
//$server = "sql-2k14-sp1-1.galaxy.ad";
//$conn = sqlsrv_connect( $server, array( "Database" => "tempdb", "UID"=>"sa", "PWD"=>"Moonshine4me"));

require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if ( $conn === false ) {
   die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "DROP TABLE dbo.ScrollTest" );
if ( $stmt !== false ) { 
   sqlsrv_free_stmt( $stmt ); 
}

$stmt = sqlsrv_query( $conn, "CREATE TABLE ScrollTest (id int, value char(10))" );
if ( $stmt === false ) {
   die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 1, "Row 1" ));
if ( $stmt === false ) {
   die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 2, "Row 2" ));
if ( $stmt === false ) {
   die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 3, "Row 3" ));
if ( $stmt === false ) {
   die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'keyset' ));
// $stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'dynamic' ));
// $stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'static' ));

$rows = sqlsrv_has_rows( $stmt );
if ( $rows != true ) {
   die( "Should have rows" );
}

$result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_LAST );
$field1 = sqlsrv_get_field( $stmt, 0 );
$field2 = sqlsrv_get_field( $stmt, 1 );
echo "\n$field1 $field2\n";

//$stmt2 = sqlsrv_query( $conn, "delete from ScrollTest where id = 3" );
// or
$stmt2 = sqlsrv_query( $conn, "UPDATE ScrollTest SET id = 4 WHERE id = 3" );
if ( $stmt2 !== false ) { 
   sqlsrv_free_stmt( $stmt2 ); 
}

$result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_LAST );
$field1 = sqlsrv_get_field( $stmt, 0 );
$field2 = sqlsrv_get_field( $stmt, 1 );
echo "\n$field1 $field2\n";

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--
3 Row 3     

4 Row 3