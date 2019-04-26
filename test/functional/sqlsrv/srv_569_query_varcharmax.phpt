--TEST--
GitHub issue #569 - sqlsrv_query on varchar max fields results in function sequence error
--DESCRIPTION--
Verifies that the problem is no longer reproducible.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
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

// This test requires to connect with the Always Encrypted feature
// First check if the system is qualified to run this test
$options = array("Database" => $database, "UID" => $userName, "PWD" => $userPassword);
$conn = sqlsrv_connect($server, $options);
if ($conn === false) {
    fatalError("Failed to connect to $server.");
}

$qualified = AE\isQualified($conn);
if ($qualified) {
    sqlsrv_close($conn);

    // Now connect with ColumnEncryption enabled
    $connectionOptions = array_merge($options, array('ColumnEncryption' => 'Enabled'));
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        fatalError("Failed to connect to $server.");
    }
}

$tableName = 'srvTestTable_569';

dropTable($conn, $tableName);

if ($qualified && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $tsql = "CREATE TABLE $tableName ([c1] varchar(max) COLLATE Latin1_General_BIN2 ENCRYPTED WITH (ENCRYPTION_TYPE = deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = AEColumnKey))";
} else {
    $tsql = "CREATE TABLE $tableName ([c1] varchar(max))";
}

$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("Failed to create $tableName");
}

$input = 'some very large string';
$stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1) VALUES (?)", array($input));
sqlsrv_execute($stmt);

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