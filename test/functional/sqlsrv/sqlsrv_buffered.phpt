--TEST--
Read, Update, Insert from a SQLSRV stream with buffered query
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

function insertOneRow($conn, $tableName)
{
    $result = null;
    if (AE\isColEncrypted()) {
        $data = array("Field2" => "This is field 2.",
                      "Field3" => "010203",
                      "Field4" => "This is field 4.",
                      "Field5" => "040506",
                      "Field6" => "This is field 6.",
                      "Field7" => "This is field 7.");
        $query = AE\getInsertSqlPlaceholders($tableName, $data);

        $stmt = sqlsrv_prepare($conn, $query, array_values($data), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    } else {
        $query = "INSERT INTO $tableName ([Field2], [Field3], [Field4], [Field5], [Field6], [Field7]) VALUES ('This is field 2.', 0x010203, 'This is field 4.', 0x040506, 'This is field 6.', 'This is field 7.' )";
        $stmt = sqlsrv_prepare($conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    }

    if (!$stmt) {
        fatalError("insertOneRow: query could not be prepared.\n");
    }
    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        fatalError("insertOneRow: failed to insert data!\n");
    }
    return $stmt;
}

function updateRow($conn, $tableName, $updateField, $params)
{
    $condField = 'Field7';
    $condition = 'This is field 7.';

    if (AE\isColEncrypted()) {
        $query = "UPDATE $tableName SET $updateField=? WHERE $condField = ?";
        array_push($params, $condition);
    } else {
        $query = "UPDATE $tableName SET $updateField=? WHERE $condField = '$condition'";
    }
    $stmt = sqlsrv_prepare($conn, $query, $params, array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    if (!$stmt) {
        fatalError("updateRow: query could not be prepared.\n");
    }
    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        fatalError("updateRow: failed to update $updateField!\n");
    }
    sqlsrv_free_stmt($stmt);
}

$conn = AE\connect();
$tableName = 'PhpCustomerTable';

// Create the test table and insert one row
$columns = array(new AE\ColumnMeta('int', 'Id', 'NOT NULL Identity (100,2) PRIMARY KEY'),
                 new AE\ColumnMeta('text', 'Field2'),
                 new AE\ColumnMeta('image', 'Field3'),
                 new AE\ColumnMeta('ntext', 'Field4'),
                 new AE\ColumnMeta('varbinary(max)', 'Field5'),
                 new AE\ColumnMeta('varchar(max)', 'Field6'),
                 new AE\ColumnMeta('nvarchar(max)', 'Field7'));

$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table for the test\n");
}
$stmt = insertOneRow($conn, $tableName);

$f2 = fopen('php://memory', 'a');
fwrite($f2, 'Update field 2.');
rewind($f2);
$f3 = fopen('php://memory', 'a');
fwrite($f3, 0x010204);
rewind($f3);
$f4 = fopen('php://memory', 'a');
fwrite($f4, 'Update field 4.');
rewind($f4);
$f5 = fopen('php://memory', 'a');
fwrite($f5, 0x040503);
rewind($f5);
$f6 = fopen('php://memory', 'a');
fwrite($f6, 'Update field 6.');
rewind($f6);
$f7 = fopen('php://memory', 'a');
fwrite($f7, 'Update field 7.');
rewind($f7);

// Update data in the table
$params = array(array(&$f2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_TEXT));
updateRow($conn, $tableName, 'Field2', $params);

$params = array(array(&$f3, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE));
updateRow($conn, $tableName, 'Field3', $params);

$params = array(array(&$f4, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NTEXT));
updateRow($conn, $tableName, 'Field4', $params);

$params = array(array(&$f5, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')));
updateRow($conn, $tableName, 'Field5', $params);

$params = array(array(&$f6, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('MAX')));
updateRow($conn, $tableName, 'Field6', $params);

$params = array(array(&$f7, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR('MAX')));
updateRow($conn, $tableName, 'Field7', $params);

// Fetch data from the table
$stmt = AE\executeQueryEx($conn, "SELECT * FROM [PhpCustomerTable]", array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
sqlsrv_fetch($stmt);

$field = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get text field\n");
} else {
    $field = str_replace("\0", "", $field);
    print("$field\n");
}

$field = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get text field\n");
} else {
    print("$field\n");
}

$field = sqlsrv_get_field($stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get image field\n");
} else {
    print("$field\n");
}

$field = sqlsrv_get_field($stmt, 3, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get ntext field\n");
} else {
    print("$field\n");
}

$field = sqlsrv_get_field($stmt, 4, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get varbinary(max) field\n");
} else {
    print("$field\n");
}

$field = sqlsrv_get_field($stmt, 5, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get varchar(max) field\n");
} else {
    print("$field\n");
}

$field = sqlsrv_get_field($stmt, 6, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!$field) {
    print("Failed to get nvarchar(max) field\n");
} else {
    print("$field\n");
}

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
100
Update field 2.
3636303532
Update field 4.
323633343237
Update field 6.
Update field 7.
