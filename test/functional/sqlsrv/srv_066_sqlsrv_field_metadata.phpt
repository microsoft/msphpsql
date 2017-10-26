--TEST--
Extended ASCII column name with UTF8 w/o BOM file encoding
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");
$tableName = "UnicodeColNameTest";

$conn = connect(array( 'CharacterSet'=>'UTF-8' ));

$tableName = "UnicodeColNameTest";

dropTable($conn, $tableName);

// Column names array
$colName = ['C1', "C2", "C3"]; // WORKING REFERENCE
$colName = ["C1Ð", "CÐÐÆØ", str_repeat("CÐÆØ", 32)];

// Create table
$stmt = sqlsrv_query($conn, "create table ".$tableName
    ." ($colName[0] VARCHAR(10), $colName[1] VARCHAR(20), $colName[2] INT)");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Prepare the statement
$query = "SELECT * FROM ".$tableName;
$stmt = sqlsrv_prepare($conn, $query);

// Get field metadata
foreach (sqlsrv_field_metadata($stmt) as $fieldMetadata) {
    $res = $fieldMetadata;
    var_dump($res['Name']);
}
dropTable($conn, $tableName);
// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
string(4) "C1Ð"
string(9) "CÐÐÆØ"
string(224) "CÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØCÐÆØ"
Done
