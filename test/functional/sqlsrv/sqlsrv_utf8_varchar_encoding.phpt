--TEST--
Test SQLSRV_ENC_UTF8_VARCHAR binds parameters as VARCHAR with UTF-8 data
--DESCRIPTION--
Verifies that using CharacterSet utf-8-varchar sends string parameters as
SQL_VARCHAR/SQL_C_CHAR (not SQL_WVARCHAR/SQL_C_WCHAR), allowing efficient
use of VARCHAR columns with _UTF8 collations without implicit NVARCHAR conversion.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsErrors', 0);
require_once('MsCommon.inc');

$conn = connect(array('CharacterSet' => SQLSRV_ENC_UTF8_VARCHAR));
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$tbname = '#utf8_varchar_test_' . rand(0, 1000);

// Create table with VARCHAR columns using UTF-8 collation
$sql = "CREATE TABLE $tbname (
    id int IDENTITY(1,1) NOT NULL,
    name varchar(255) COLLATE Latin1_General_100_CI_AS_SC_UTF8 NULL,
    data varchar(max) COLLATE Latin1_General_100_BIN2_UTF8 NOT NULL
)";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$testCases = [
    [
        'label' => 'basic_ascii',
        'name'  => 'Hello World',
        'data'  => '{"text": "Hello World"}',
    ],
    [
        'label' => 'european_utf8',
        'name'  => 'Straße Köln Grüß Gott',
        'data'  => '{"text": "Straße Köln Grüß Gott"}',
    ],
    [
        'label' => 'multi_script',
        'name'  => '日本語 русский عربى',
        'data'  => '{"text": "日本語 русский عربى"}',
    ],
];

echo "=== Insert and roundtrip with SQLSRV_ENC_UTF8_VARCHAR ===\n";

foreach ($testCases as $case) {
    $stmt = sqlsrv_query($conn,
        "INSERT INTO $tbname (name, data) VALUES (?, ?)",
        [$case['name'], $case['data']]
    );
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $stmt = sqlsrv_query($conn,
        "SELECT name, data FROM $tbname WHERE name = ?",
        [$case['name']]
    );
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $nameMatch = ($case['name'] === $row['name']) ? 'YES' : 'NO';
    $dataMatch = ($case['data'] === $row['data']) ? 'YES' : 'NO';

    echo "CASE {$case['label']}: name={$nameMatch}, data={$dataMatch}\n";
}

sqlsrv_query($conn, "DROP TABLE $tbname");
sqlsrv_close($conn);
echo "Done\n";
?>
--EXPECT--
=== Insert and roundtrip with SQLSRV_ENC_UTF8_VARCHAR ===
CASE basic_ascii: name=YES, data=YES
CASE european_utf8: name=YES, data=YES
CASE multi_script: name=YES, data=YES
Done
