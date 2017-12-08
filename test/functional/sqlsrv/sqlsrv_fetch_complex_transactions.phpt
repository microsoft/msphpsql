--TEST--
Test transactions commit, rollback and aborting in between
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function complexTransaction($conn, $conn2)
{
    $tableName = 'testTransaction';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('real', 'c2_real'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    sqlsrv_free_stmt($stmt);

    $stmtSelect = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    $stmtDelete = sqlsrv_prepare($conn, "DELETE TOP(3) FROM $tableName");

    // insert ten rows
    $numRows = 10;
    insertData($conn, $tableName, $numRows);
    fetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);
    $rowsAffected = sqlsrv_rows_affected($stmtDelete);
    sqlsrv_commit($conn);
    echo "Committed deleting 3 rows\n";

    $numRows = $numRows - $rowsAffected;
    fetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);
    sqlsrv_rollback($conn);
    echo "Rolled back\n";

    fetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);
    $rowsAffected = sqlsrv_rows_affected($stmtDelete);
    sqlsrv_commit($conn);
    echo "Committed deleting 3 rows\n";

    $numRows = $numRows - $rowsAffected;
    fetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);
    sqlsrv_rollback($conn);
    echo "Rolled back\n";

    fetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);
    // disconnect first connection, transaction aborted
    sqlsrv_close($conn);
    echo "Deletion aborted\n";

    // select table using the second connection
    $stmt = sqlsrv_prepare($conn2, "SELECT * FROM $tableName");
    fetchData($stmt, $tableName, $numRows);

    dropTable($conn2, $tableName);
}

function insertData($conn, $tableName, $count)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_real) VALUES (?, ?)", array(&$v1, &$v2));

    for ($i = 0; $i < $count; $i++) {
        $v1 = $i + 1;
        $v2 = $v1 * 1.5;

        sqlsrv_execute($stmt);
    }
}

function fetchData($stmt, $tableName, $numRows)
{
    $numFetched = 0;
    sqlsrv_execute($stmt);
    while ($result = sqlsrv_fetch($stmt)) {
        $numFetched++;
    }

    echo "Number of rows fetched: $numFetched\n";
    if ($numFetched != $numRows) {
        echo "Expected $numRows rows.\n";
    }
}

try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    echo "\nTest begins...\n";

    // Connect
    $conn = AE\connect();
    $conn2 = AE\connect();

    complexTransaction($conn, $conn2);

    sqlsrv_close($conn2);    // $conn should have been closed
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿﻿
Test begins...
Number of rows fetched: 10
Committed deleting 3 rows
Number of rows fetched: 7
Rolled back
Number of rows fetched: 7
Committed deleting 3 rows
Number of rows fetched: 4
Rolled back
Number of rows fetched: 4
Deletion aborted
Number of rows fetched: 4

Done
