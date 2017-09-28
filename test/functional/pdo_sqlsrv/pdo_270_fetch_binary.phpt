--TEST--
Test fetch from binary, varbinary, varbinary(max), image columns, without setting binary encoding.
--DESCRIPTION--
Verifies GitHub issue 270 is fixed, users could not retrieve the data as inserted in binary columns without setting the binary encoding either on stmt or using bindCoulmn encoding.
This test verifies that the data inserted in binary columns can be retrieved using fetch, fetchColumn, fetchObject, and fetchAll functions.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );

$tableName = 'test_binary'.rand();
$columns = array( 'col1', 'col2', 'col3' );

// Connect
$conn = connect();

$colmeta_arr = array( new columnMeta( "binary(50)", $columns[0] ), new columnMeta( "varbinary(50)", $columns[1] ), new columnMeta( "varbinary(max)", $columns[2] ));
$icon = base64_decode("This is some text to test retrieving from binary type columns");
$inputs = array( $columns[0] => new bindParamOp( 1, $icon, "PDO::PARAM_LOB", null, "PDO::SQLSRV_ENCODING_BINARY" ),
                 $columns[1] => new bindParamOp( 2, $icon, "PDO::PARAM_LOB", null, "PDO::SQLSRV_ENCODING_BINARY" ),
                 $columns[2] => new bindParamOp( 3, $icon, "PDO::PARAM_LOB", null, "PDO::SQLSRV_ENCODING_BINARY" ));
                 
if ( !is_col_encrypted() )
{
    // image is not supported for encryption
    array_push( $columns, 'col4' );
    array_push( $colmeta_arr, new columnMeta( "image", $columns[3] ));
    array_merge( $inputs, array( $columns[3] => new bindParamOp( 4, $icon, "PDO::PARAM_LOB", null, "PDO::SQLSRV_ENCODING_BINARY" )));
}
                 
create_table( $conn, $tableName, $colmeta_arr);

// Insert data using bind parameters
insert_row( $conn, $tableName, $inputs, "prepareBindParam" );

// loop through each column in the table
foreach ($columns as $col){
    test_fetch($conn, $tableName, $col, $icon);
}
// DROP table
DropTable( $conn, $tableName );

//free statement and connection
unset( $stmt );
unset( $conn );

print_r("Test finished successfully\n");

//calls various fetch methods
function test_fetch($conn, $tableName, $columnName, $input){
    
    $len = strlen($input);
    $result = "";
    $sql = "SELECT $columnName from $tableName";
    
    $stmt = $conn->query($sql);  
    $stmt->bindColumn(1, $result, PDO::PARAM_LOB);
    $stmt->fetch(PDO::FETCH_BOUND);
    //binary is fixed size, to evaluate output, compare it using strncmp
    if( strncmp($result, $input, $len) !== 0){
        print_r("\nRetrieving using bindColumn failed");
    }

    $result = "";
    $stmt = $conn->query($sql);      
    $stmt->bindColumn(1, $result, PDO::PARAM_LOB, 0 , PDO::SQLSRV_ENCODING_BINARY);
    $stmt->fetch(PDO::FETCH_BOUND);
    if( strncmp($result, $input, $len) !== 0){
        print_r("\nRetrieving using bindColumn with encoding set failed");
    }

    $result = "";
    $stmt = $conn->query($sql);  
    $result = $stmt->fetchColumn();
    if( strncmp($result, $input, $len) !== 0){
        print_r("\nRetrieving using fetchColumn failed");
    }

    $result = "";
    $stmt = $conn->query($sql);  
    $result = $stmt->fetchObject();
    if( strncmp($result->$columnName, $input, $len) !== 0){
        print_r("\nRetrieving using fetchObject failed");
    }

    $result = "";
    $stmt = $conn->query($sql);  
    $result = $stmt->fetchAll( PDO::FETCH_COLUMN );
    if( strncmp($result[0], $input, $len) !== 0){
        print_r("\nRetrieving using fetchAll failed");
    }
    unset( $stmt );
}

?>
--EXPECT--
Test finished successfully
