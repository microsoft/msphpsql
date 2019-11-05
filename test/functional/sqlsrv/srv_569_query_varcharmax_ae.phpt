--TEST--
GitHub issue #569 - sqlsrv_query on varchar max fields results in function sequence error
--DESCRIPTION--
This is similar to srv_569_query_varcharmax.phpt but is not limited to testing the Always Encrypted feature in Windows only.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect(array('CharacterSet'=>'UTF-8'));

$tableName = 'srvTestTable_569_ae';

$columns = array(new AE\ColumnMeta('int', 'id', 'identity'),
                 new AE\ColumnMeta('nvarchar(max)', 'c1'));
AE\createTable($conn, $tableName, $columns);

$input = array();

$input[0] = 'some very large string';
$input[1] = '1234567890.1234';
$input[2] = 'über über';

$numRows = 3;
$isql = "INSERT INTO $tableName (c1) VALUES (?)";
for ($i = 0; $i < $numRows; $i++) {
    $stmt = sqlsrv_prepare($conn, $isql, array($input[$i]));
    $result = sqlsrv_execute($stmt);
    if (!$result) {
        fatalError("Failed to insert row $i into $tableName");
    }
}

// Select all from test table
$tsql = "SELECT id, c1 FROM $tableName ORDER BY id";
$stmt = sqlsrv_prepare($conn, $tsql);
if (!$stmt) {
    fatalError("Failed to read from $tableName");
}
$result = sqlsrv_execute($stmt);
if (!$result) {
    fatalError("Failed to select data from $tableName");
}

// Fetch each row as an array
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $i = $row['id'] - 1;
    if ($row['c1'] !== $input[$i]) {
        echo "Expected $input[$i] but got: ";
        var_dump($fieldVal);
    }
}

// Fetch again, one field each time
sqlsrv_execute($stmt);

$i = 0;
while ($i < $numRows) {
    sqlsrv_fetch($stmt);

    switch ($i) {
    case 0:
        $fieldVal = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        break;
    case 1:
        $stream = sqlsrv_get_field($stmt, 1);
        while (!feof( $stream)) {
            $fieldVal = fread($stream, 50);
        }
        break;
    default:
        $fieldVal = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING('utf-8'));
        break;
    }

    if ($fieldVal !== $input[$i]) {
        echo 'Expected $input[$i] but got: ';
        var_dump($fieldVal);
    }

    $i++;
}

dropTable($conn, $tableName);

echo "Done\n";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
Done