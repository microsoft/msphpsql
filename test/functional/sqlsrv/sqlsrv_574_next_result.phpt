--TEST--
GitHub issue 574 - Fetch Next Result Test
--DESCRIPTION--
Verifies the functionality of sqlsrv_next_result
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

$conn = AE\connect();
$tableName = 'test574';
$tableName1 = 'test574_1';

// create two tables with max fields
$columns = array(new AE\ColumnMeta('varchar(max)', 'col1'));
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table for the test\n");
}

$columns = array(new AE\ColumnMeta('varchar(max)', 'col1'));
$stmt = AE\createTable($conn, $tableName1, $columns);
if (!$stmt) {
    fatalError("Failed to create table for the test\n");
}

// insert one row to each table
$sql = "insert into $tableName (col1) VALUES (?)";
$phrase = str_repeat('This is a test ', 25000);

$stmt = sqlsrv_prepare($conn, $sql, array($phrase));
if ($stmt) {
    $r = sqlsrv_execute($stmt);
    if (!$r) {
        print_r(sqlsrv_errors());
    }
}

$phrase1 = str_repeat('This is indeed very long ', 30000);
$sql = "insert into $tableName1 (col1) VALUES (?)";
$stmt = sqlsrv_prepare($conn, $sql, array($phrase1));
if ($stmt) {
    $r = sqlsrv_execute($stmt);
    if (!$r) {
        print_r(sqlsrv_errors());
    }
}

// run queries in a batch
$stmt = sqlsrv_prepare($conn, "SELECT * FROM [$tableName]; SELECT artist FROM [cd_info]; SELECT * FROM [$tableName1]");
if ($stmt) {
    $r = sqlsrv_execute($stmt);
    if (!$r) {
        print_r(sqlsrv_errors());
    }
}

// fetch from $tableName
$row = sqlsrv_fetch($stmt);
if ($row) {
    $fld = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($fld === $phrase) {
        echo(substr($fld, 0, 15)) . PHP_EOL;
    } else {
        echo "Incorrect value substr($fld, 0, 1000)...!" . PHP_EOL;
    }
}

// fetch from cd_info
echo "1. next result? ";
$next = sqlsrv_next_result($stmt);
var_dump($next);

$row = sqlsrv_fetch($stmt);
if ($row) {
    $fld = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    echo $fld . PHP_EOL;
}

// fetch from $tableName1
echo "2. next result? ";
$next = sqlsrv_next_result($stmt);
var_dump($next);

$row = sqlsrv_fetch($stmt);
if ($row) {
    $fld = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($fld === $phrase1) {
        echo(substr($fld, 0, 25)) . PHP_EOL;
    } else {
        echo "Incorrect value substr($fld, 0, 1000)...!" . PHP_EOL;
    }
}

// should be no more next results, first returns NULL second returns false
echo "3. next result? ";
$next = sqlsrv_next_result($stmt);
var_dump($next);

$row = sqlsrv_fetch($stmt);
if ($row) {
    fatalError("This is unexpected!\n");
}

echo "4. next result? ";
$next = sqlsrv_next_result($stmt);
var_dump($next);
sqlsrv_free_stmt($stmt);

// run queries in a batch again, different order this time
$stmt = sqlsrv_prepare($conn, "SELECT * FROM [$tableName1]; SELECT * FROM [$tableName]; SELECT artist FROM [cd_info]");
if ($stmt) {
    $r = sqlsrv_execute($stmt);
    if (!$r) {
        print_r(sqlsrv_errors());
    }
}
// skip the first two queries
sqlsrv_next_result($stmt);
sqlsrv_next_result($stmt);

// fetch from cd_info
$row = sqlsrv_fetch($stmt);
if ($row) {
    $fld = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    echo $fld . PHP_EOL;
}

// re-execute the statement, should return to the first query in the batch
$r = sqlsrv_execute($stmt);
if (!$r) {
    print_r(sqlsrv_errors());
}

// fetch from $tableName1
$row = sqlsrv_fetch($stmt);
if ($row) {
    $fld = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($fld === $phrase1) {
        echo(substr($fld, 0, 25)) . PHP_EOL;
    } else {
        echo "Incorrect value substr($fld, 0, 1000)...!" . PHP_EOL;
    }
}
sqlsrv_free_stmt($stmt);

// execute a simple query, no more batch
$stmt = sqlsrv_prepare($conn, "SELECT * FROM [$tableName]");
if ($stmt) {
    $r = sqlsrv_execute($stmt);
    if (!$r) {
        print_r(sqlsrv_errors());
    }
}

// fetch from $tableName
$row = sqlsrv_fetch($stmt);
if ($row) {
    $fld = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($fld === $phrase) {
        echo(substr($fld, 0, 15)) . PHP_EOL;
    } else {
        echo "Incorrect value substr($fld, 0, 1000)...!" . PHP_EOL;
    }
}

// should be no more next results, first returns NULL second returns false
echo "5. next result? ";
$next = sqlsrv_next_result($stmt);
var_dump($next);

echo "6. next result? ";
$next = sqlsrv_next_result($stmt);
var_dump($next);

dropTable($conn, $tableName);
dropTable($conn, $tableName1);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done\n";
?>

--EXPECT--
This is a test 
1. next result? bool(true)
Led Zeppelin
2. next result? bool(true)
This is indeed very long 
3. next result? NULL
4. next result? bool(false)
Led Zeppelin
This is indeed very long 
This is a test 
5. next result? NULL
6. next result? bool(false)
Done