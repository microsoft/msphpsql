--TEST--
Unicode column names
--SKIPIF--
--FILE--
<?php
require_once('MsCommon.inc');
$tableName = "UnicodeColNameTest";

setup();
$conn = connect(array( 'CharacterSet'=>'UTF-8' ));

$tableName = "UnicodeColNameTest";

dropTable($conn, $tableName);

// Column names array
$colName = ["P_".'银河系', str_repeat("金星", 2), "CÐÐÆØ"];

// Create table
$stmt = sqlsrv_query($conn, "create table ".$tableName
    ." ($colName[0] VARCHAR(10), $colName[1] VARCHAR(20), $colName[2] INT)");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

// Insert data
$sql = "INSERT INTO ".$tableName." VALUES ('Nick', 'Lee', 30)";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_free_stmt($stmt);

// Insert data
$sql = "INSERT INTO ".$tableName." VALUES ('Nhoj', 'Eoduard', -3),('Vi Lo', N'N/A', 1987)";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_free_stmt($stmt);

// Prepare the statement
$query = "SELECT * FROM ".$tableName;
$stmt = sqlsrv_prepare($conn, $query);

// Get field metadata
foreach (sqlsrv_field_metadata($stmt) as $fieldMetadata) {
    $res = $fieldMetadata;
    var_dump($res['Name']);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
string(11) "P_银河系"
string(12) "金星金星"
string(9) "CÐÐÆØ"
Done
