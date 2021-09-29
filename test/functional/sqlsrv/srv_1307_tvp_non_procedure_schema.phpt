--TEST--
Verify Github Issue 1307 is fixed but TVP and table are defined in a different schema
--DESCRIPTION--
To show that table-valued parameters work with non-procedure statements
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

function cleanup($conn, $tvpname, $testTable, $schema)
{
    $dropTableType = dropTableTypeSQL($conn, $tvpname, $schema);
    sqlsrv_query($conn, $dropTableType);
    sqlsrv_query($conn, "DROP TABLE IF EXISTS [$schema].[$testTable]");
    sqlsrv_query($conn, "DROP SCHEMA IF EXISTS [$schema]");
}

function readData($conn, $schema, $testTable)
{
    $tsql = "SELECT id FROM [$schema].[$testTable] ORDER BY id";
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

$tvpname = 'srv_id_table2';
$testTable = 'srv_test_table2';
$schema = 'srv schema';

cleanup($conn, $tvpname, $testTable, $schema);

// Create the schema
$tsql = "CREATE SCHEMA [$schema]";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Create the table type and test table
$tsql = "CREATE TYPE [$schema].[$tvpname] AS TABLE(id INT PRIMARY KEY)";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

$tsql = "CREATE TABLE [$schema].[$testTable] (id INT PRIMARY KEY)";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Populate the table using the table type
$tsql = "INSERT INTO [$schema].[$testTable] SELECT * FROM ?";
$params = [
[[$tvpname => [[15], [13], [11]], $schema]],
];

$stmt = sqlsrv_query($conn, $tsql, $params);
if (!$stmt) {
    print_r(sqlsrv_errors());
}
sqlsrv_free_stmt($stmt);

// Verify the results
readData($conn, $schema, $testTable);

// Use Merge statement next
$tsql = <<<QRY
MERGE INTO [$schema].[$testTable] t
USING ? s ON s.id = t.id
WHEN NOT MATCHED THEN 
INSERT (id) VALUES(s.id);
QRY;

$params = [
[[$tvpname => [[10], [16], [14], [12]], $schema]],
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
readData($conn, $schema, $testTable);

cleanup($conn, $tvpname, $testTable, $schema);

echo "Done\n";

sqlsrv_close($conn);
?>
--EXPECT--
11
13
15
10
11
12
13
14
15
16
Done
