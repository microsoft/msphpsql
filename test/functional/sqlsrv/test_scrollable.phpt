--TEST--
scrollable result sets.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', false );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );

$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('ScrollTest', 'U') IS NOT NULL DROP TABLE ScrollTest" );
if( $stmt !== false ) { sqlsrv_free_stmt( $stmt ); }

$stmt = sqlsrv_query( $conn, "CREATE TABLE ScrollTest (id int, value char(10))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_has_rows( $stmt );
if( $rows == true ) {
    die( "Shouldn't have rows" );
}
sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 1, "Row 1" ));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_has_rows( $stmt );
if( $rows == true ) {
    die( "Shouldn't have rows" );
}
$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 2, "Row 2" ));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_has_rows( $stmt );
if( $rows == true ) {
    die( "Shouldn't have rows" );
}
$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 3, "Row 3" ));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_has_rows( $stmt );
if( $rows == true ) {
    die( "Shouldn't have rows" );
}
$stmt = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 4, "Row 4" ));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_has_rows( $stmt );
if( $rows == true ) {
    die( "Shouldn't have rows" );
}

$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'static' ));

$result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_ABSOLUTE, 4 );
if( $result !== null ) {
    die( "Should have failed with an invalid row number" );
}
$rows = sqlsrv_has_rows( $stmt );
if( $rows != true ) {
    die( "Should have rows" );
}
print_r( sqlsrv_errors() );
$result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_ABSOLUTE, -1 );
if( $result !== null ) {
    die( "Should have failed with an invalid row number" );
}
print_r( sqlsrv_errors() );

$rows = sqlsrv_rows_affected( $stmt );
print_r( sqlsrv_errors() );

$rows = sqlsrv_num_rows( $stmt );
echo "Query returned $rows rows\n";

$row = 3;
$result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_ABSOLUTE, $row );
do { 
    $result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_ABSOLUTE, $row );
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $field1 = sqlsrv_get_field( $stmt, 0 );
    $field2 = sqlsrv_get_field( $stmt, 1 );
    echo "$field1 $field2\n";
    $row = $row - 1;
} while( $row >= 0 );

sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => SQLSRV_CURSOR_FORWARD ));
$rows = sqlsrv_has_rows( $stmt );
if( $rows != true ) {
    die( "Should have rows" );
}
$row_count = 0;
while( $row = sqlsrv_fetch( $stmt )) {
       ++$row_count;
}
if( $row === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
echo "$row_count rows retrieved on the forward only cursor\n";
sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'static' ));
$rows = sqlsrv_has_rows( $stmt );
if( $rows != true ) {
    die( "Should have rows" );
}
$row_count = 0;
while( $row = sqlsrv_fetch( $stmt )) {
       ++$row_count;
}
if( $row === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
echo "$row_count rows retrieved on the static cursor\n";
sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'dynamic' ));

sqlsrv_fetch( $stmt );
sqlsrv_fetch( $stmt );

$stmt2 = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 5, "Row 5" ));
if( $stmt2 === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt2 = sqlsrv_query( $conn, "INSERT INTO ScrollTest (id, value) VALUES(?,?)", array( 6, "Row 6" ));
if( $stmt2 === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$row_count = 2; // for the two fetches above
while( sqlsrv_fetch( $stmt )) {
       ++$row_count;
}
echo "$row_count rows retrieved on the dynamic cursor\n";
sqlsrv_free_stmt( $stmt );


$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => SQLSRV_CURSOR_STATIC ));
$row_count = sqlsrv_num_rows( $stmt );
if( $row_count != 6 ) {
    die( "sqlsrv_num_rows should have returned 6 rows in the static cursor\n" );
}
$row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, -1 );
if( $row !== null ) {
    die( "sqlsrv_fetch_array should have returned null\n" );
}

$row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, 6 );
if( $row !== null ) {
    die( "sqlsrv_fetch_array should have returned null\n" );
}

$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => SQLSRV_CURSOR_DYNAMIC ));

$result = sqlsrv_num_rows( $stmt );
if( $result !== false ) {
    die( "sqlsrv_num_rows should have failed for a dynamic cursor." );
}
sqlsrv_fetch( $stmt );
sqlsrv_fetch( $stmt );

$stmt2 = sqlsrv_query( $conn, "DELETE FROM ScrollTest WHERE id = 2" );
if( $stmt2 === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$row = sqlsrv_get_field( $stmt, 0 );
if( $row !== false ) {
    die( "Should have returned false retrieving a field deleted by another query\n" );
}
echo "sqlsrv_get_field returned false when retrieving a field deleted by another query\n";
print_r( sqlsrv_errors() );

// verify the sqlsrv_fetch_object is working
$obj = sqlsrv_fetch_object( $stmt, null, array(null), SQLSRV_SCROLL_LAST, 1 );

if( $obj === false ) {
    
    print_r( sqlsrv_errors() );
}
print_r( $obj );

sqlsrv_query( $conn, "DROP TABLE ScrollTest" );

sqlsrv_free_stmt( $stmt );

sqlsrv_close( $conn );

echo "Test succeeded.\n";

?>
--EXPECTREGEX-- 
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => \-51
            \[code\] => \-51
            \[2\] => This function only works with statements that are not scrollable\.
            \[message\] => This function only works with statements that are not scrollable\.
        \)

\)
Query returned 4 rows
4 Row 4     
3 Row 3     
2 Row 2     
1 Row 1     
4 rows retrieved on the forward only cursor
4 rows retrieved on the static cursor
6 rows retrieved on the dynamic cursor
sqlsrv_get_field returned false when retrieving a field deleted by another query
Array
\(
    \[0\] => Array
        \(
            \[0\] => HY109
            \[SQLSTATE\] => HY109
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid cursor position
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid cursor position
        \)

\)
stdClass Object
\(
    \[id\] => 6
    \[value\] => Row 6     
\)
Test succeeded\.
