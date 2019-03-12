--TEST--
GitHub issue #569 - sqlsrv_query on varchar max fields results in function sequence error
--DESCRIPTION--
Verifies that the problem is no longer reproducible.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

function verifyFetchError()
{
    $expected = 'A row must be retrieved with sqlsrv_fetch before retrieving data with sqlsrv_get_field.';
    if (strpos(sqlsrv_errors()[0]['message'], $expected) === false) {
        print_r(sqlsrv_errors());
    }
}

require_once('MsCommon.inc');

$conn = AE\connect();

$tableName = 'srvTestTable_569';
$colMetaArr = array(new AE\ColumnMeta('varchar(max)', 'col1'));
AE\createTable($conn, $tableName, $colMetaArr);

$input = 'some very large string';
$stmt = AE\insertRow($conn, $tableName, array('col1' => $input));

$tsql = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("Failed to read from $tableName");
}

sqlsrv_fetch($stmt);
$fieldVal = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));

if ($fieldVal !== $input) {
    echo "Expected $input but got: ";
    var_dump($fieldVal);
}

$tsql2 = "DELETE FROM $tableName";
$stmt = sqlsrv_query($conn, $tsql2);
if (!$stmt) {
    fatalError("Failed to delete rows from $tableName");
}

$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("Failed to read $tableName, now empty");
}

$result = sqlsrv_fetch($stmt);
if (!is_null($result)) {
    echo 'Expected null when fetching an empty table but got: ';
    var_dump($result);
}

$fieldVal = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
verifyFetchError();
if ($fieldVal !== false) {
    echo 'Expected bool(false) but got: ';
    var_dump($fieldVal);
}

$stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"buffered"));
$result = sqlsrv_fetch($stmt);
if (!is_null($result)) {
    echo 'Expected null when fetching an empty table but got: ';
    var_dump($result);
}

$fieldVal = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
verifyFetchError();
if ($fieldVal !== false) {
    echo 'Expected bool(false) but got: ';
    var_dump($fieldVal);
}


dropTable($conn, $tableName);

echo "Done\n";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
Done