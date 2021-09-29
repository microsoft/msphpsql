--TEST--
Verify Github Issue 1307 is fixed.
--DESCRIPTION--
To show that table-valued parameters work with non-procedure statements
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

function cleanup($conn, $tvpname, $testTable)
{
    $dropTableType = dropTableTypeSQL($conn, $tvpname);
    sqlsrv_query($conn, $dropTableType);
    sqlsrv_query($conn, "DROP TABLE IF EXISTS [$testTable]");
}

function readData($conn, $testTable)
{
    $tsql = "SELECT id FROM $testTable ORDER BY id";
    $stmt = sqlsrv_query($conn, $tsql);
    if (!$stmt) {
        print_r(sqlsrv_errors());
    }
    while ($result = sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC)) {
        $ID = sqlsrv_get_field($stmt, 0);
        echo $ID . PHP_EOL;
    }
    sqlsrv_free_stmt($stmt);
}

$conn = connect();

$tvpname = 'srv_id_table';
$testTable = 'srv_test_table';

cleanup($conn, $tvpname, $testTable);

// Create the table type and test table
$tsql = "CREATE TYPE $tvpname AS TABLE(id INT PRIMARY KEY)";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

$tsql = "CREATE TABLE $testTable (id INT PRIMARY KEY)";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Populate the table using the table type
$tsql = "INSERT INTO $testTable SELECT * FROM ?";
$params = [
[[$tvpname => [[5], [7], [9]]]],
];

$stmt = sqlsrv_query($conn, $tsql, $params);
if (!$stmt) {
    print_r(sqlsrv_errors());
}
sqlsrv_free_stmt($stmt);

// Verify the results
readData($conn, $testTable);

// Use Merge statement next
$tsql = <<<QRY
MERGE INTO $testTable t
USING ? s ON s.id = t.id
WHEN NOT MATCHED THEN 
INSERT (id) VALUES(s.id);
QRY;

$params = [
[[$tvpname => [[2], [6], [4], [8], [3]]]],
];

$stmt = sqlsrv_prepare($conn, $tsql, $params);
if (!$stmt) {
    print_r(sqlsrv_errors());
}
$result = sqlsrv_execute($stmt);
if (!$result) {
    print_r(sqlsrv_errors());
}

// Verify the results
readData($conn, $testTable);

cleanup($conn, $tvpname, $testTable);

echo "Done\n";

sqlsrv_close($conn);
?>
--EXPECT--
5
7
9
2
3
4
5
6
7
8
9
Done
