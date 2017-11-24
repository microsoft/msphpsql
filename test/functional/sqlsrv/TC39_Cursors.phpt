--TEST--
Cursor Mode Test
--DESCRIPTION--
Verifies the functionality associated with scrollable resultsets.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function cursorTest($noRows1, $noRows2)
{
    $testName = "Statement - Cursor Mode";
    startTest($testName);

    setup();
    $conn1 = AE\connect();

    $tableName = 'TC39test';
    
    $cursor = "";
    for ($k = 0; $k < 4; $k++) {
        switch ($k) {
            case 0: $cursor = SQLSRV_CURSOR_FORWARD;    break;
            case 1: $cursor = SQLSRV_CURSOR_STATIC;     break;
            case 2: $cursor = SQLSRV_CURSOR_DYNAMIC;    break;
            case 3: $cursor = SQLSRV_CURSOR_KEYSET;     break;
            default:                                    break;
        }
        scrollableFetch($conn1, $tableName, $noRows1, $noRows2, $cursor);
    }

    sqlsrv_close($conn1);

    endTest($testName);
}

function scrollableFetch($conn, $tableName, $noRows1, $noRows2, $cursor)
{
    $colIndex = "c27_timestamp";
    $tableIndex = "TC39index";

    AE\createTestTable($conn, $tableName);
    createUniqueIndexEx($conn, $tableName, $tableIndex, $colIndex);

    $stmt1 = AE\selectFromTable($conn, $tableName);
    if (sqlsrv_has_rows($stmt1)) {
        die("Table $tableName is expected to be empty...");
    }
    sqlsrv_free_stmt($stmt1);

    $noRows = AE\insertTestRows($conn, $tableName, $noRows1);

    $query = "SELECT * FROM [$tableName] ORDER BY $colIndex";
    $options = array('Scrollable' => $cursor);

    $stmt2 = sqlsrv_query($conn, $query, null, $options);
    if (!sqlsrv_has_rows($stmt2)) {
        die("Table $tableName is not expected to be empty...");
    }
    if (($cursor == SQLSRV_CURSOR_STATIC) ||
        ($cursor == SQLSRV_CURSOR_KEYSET)) {
        $numRows = sqlsrv_num_rows($stmt2);
        if ($numRows != $noRows) {
            die("Unexpected row count for $cursor: $numRows instead of $noRows\n");
        }
    }
    while (($noRows > 0) && sqlsrv_fetch($stmt2)) {
        // consume the result set
        $noRows--;
    }
    if ($noRows2 > 0) {
        $extraRows = AE\insertTestRows($conn, $tableName, $noRows2);
        if ($cursor == SQLSRV_CURSOR_DYNAMIC) {
            $noRows += $extraRows;
        }
    }
    while (sqlsrv_fetch($stmt2)) {
        // consume the result set
        $noRows--;
    }
    sqlsrv_free_stmt($stmt2);

    if ($noRows != 0) {
        die("Unexpected row count for $cursor: $noRows\n");
    }
    dropTable($conn, $tableName);
}

try {
    cursorTest(10, 5);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Statement - Cursor Mode" completed successfully.
