--TEST--
GitHub issue #937 - getting metadata will not fail after an UPDATE / DELETE statement
--DESCRIPTION--
Verifies that sqlsrv_field_metadata will return an empty array after processing an 
UPDATE / DELETE query that returns no fields.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = connect();
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$tableName = 'srvTestTable_938';
$procName = 'srvTestProc_938';

dropTable($conn, $tableName);
dropProc($conn, $procName);

// Create the test table
$tsql = "CREATE TABLE $tableName([id] [int] NOT NULL, 
                                 [dummyColumn] [varchar](10) NOT NULL
                                )";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}

$id = 5;
$tsql = "INSERT INTO $tableName VALUES ($id, 'dummy')";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("Failed to insert a row into table $tableName\n");
}

$tsql = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $tsql);
$fieldmeta = sqlsrv_field_metadata($stmt);
var_dump($fieldmeta);

$tsql = "UPDATE $tableName SET dummyColumn = 'updated' WHERE id = $id";
$stmt = sqlsrv_prepare($conn, $tsql);
sqlsrv_execute($stmt);
$fieldmeta = sqlsrv_field_metadata($stmt);
var_dump($fieldmeta);

createProc($conn, $procName, "@id int, @val varchar(10) OUTPUT", "SELECT @val = dummyColumn FROM $tableName WHERE id = @id");

$value = '';
$tsql = "{CALL [$procName] (?, ?)}";
$stmt = sqlsrv_prepare(
    $conn,
    $tsql,
    array(array($id, SQLSRV_PARAM_IN),
                             array(&$value, SQLSRV_PARAM_OUT)
                            )
                       );
$result = sqlsrv_execute($stmt);
if (!$result) {
    fatalError("Failed to invoke stored procedure $procName\n");
}

echo "The value returned: $value\n";

$fieldmeta = sqlsrv_field_metadata($stmt);
var_dump($fieldmeta);

$options = array("Scrollable" => "buffered");
$tsql = "DELETE FROM $tableName WHERE dummyColumn = 'updated'";
$stmt = sqlsrv_query($conn, $tsql, array(), $options);
$fieldmeta = sqlsrv_field_metadata($stmt);
var_dump($fieldmeta);

dropTable($conn, $tableName);
dropProc($conn, $procName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(2) {
  [0]=>
  array(6) {
    ["Name"]=>
    string(2) "id"
    ["Type"]=>
    int(4)
    ["Size"]=>
    NULL
    ["Precision"]=>
    int(10)
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(0)
  }
  [1]=>
  array(6) {
    ["Name"]=>
    string(11) "dummyColumn"
    ["Type"]=>
    int(12)
    ["Size"]=>
    int(10)
    ["Precision"]=>
    NULL
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(0)
  }
}
array(0) {
}
The value returned: updated
array(0) {
}
array(0) {
}