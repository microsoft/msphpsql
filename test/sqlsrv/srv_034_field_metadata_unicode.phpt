--TEST--
Field metadata unicode
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// Connect
$conn = Connect(array("CharacterSet"=>"UTF-8"));
if( !$conn ) {
    FatalError("Connection could not be established.\n");
}

$tableName = GetTempTableName();

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (पासपोर्ट CHAR(2), پاسپورٹ VARCHAR(2), Διαβατήριο VARCHAR(MAX))";
$stmt = sqlsrv_query($conn, $sql);

// Prepare the statement
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_prepare($conn, $sql);

// Get and display field metadata
foreach(sqlsrv_field_metadata($stmt) as $meta)
{
    print_r($meta);
}

// Free statement and connection resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
Array
(
    [Name] => पासपोर्ट
    [Type] => 1
    [Size] => 2
    [Precision] => 
    [Scale] => 
    [Nullable] => 1
)
Array
(
    [Name] => پاسپورٹ
    [Type] => 12
    [Size] => 2
    [Precision] => 
    [Scale] => 
    [Nullable] => 1
)
Array
(
    [Name] => Διαβατήριο
    [Type] => 12
    [Size] => 0
    [Precision] => 
    [Scale] => 
    [Nullable] => 1
)
Done
