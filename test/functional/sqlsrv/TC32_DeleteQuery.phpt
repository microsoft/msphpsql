--TEST--
Delete Query Test
--DESCRIPTION--
Executes several INSERT queries followed by DELETE queries and
validates the outcome reported by "sqlsrv_rows_affected".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function deleteQuery()
{
    $testName = "Statement - Delete Query";
    startTest($testName);

    setup();
    $conn1 = AE\connect();

    $noRows = 10;
    $tableName = 'TC32test';
    AE\createTestTable($conn1, $tableName);
    // Insert some random rows
    $noRowsInserted = AE\insertTestRows($conn1, $tableName, $noRows);

    $row = 1;
    $keyValue = "0";
    while ($row <= $noRowsInserted) {
        $stmt1 = AE\selectFromTable($conn1, $tableName);
        if (sqlsrv_fetch($stmt1) === false) {
            fatalError("Failed to retrieve 1st row of data from test table", true);
        }
        $keyValue = sqlsrv_get_field($stmt1, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        sqlsrv_free_stmt($stmt1);

        trace("Deleting rows from $tableName ...");
        $delRows = 1;
        if (strlen($keyValue) == 0) {
            $stmt2 = AE\executeQuery($conn1, "DELETE TOP(1) FROM [$tableName]");
            $cond = "(top row)";
        } else {
            $cond = "(c1_int = ?)";
            $params = array($keyValue);
            $stmt3 = AE\selectFromTable($conn1, $tableName, $cond, $params);
            $delRows = rowCount($stmt3);
            sqlsrv_free_stmt($stmt3);

            $stmt2 = AE\executeQuery($conn1, "DELETE FROM [$tableName]", $cond, $params );
        }
        $numRows1 = sqlsrv_rows_affected($stmt2);
        sqlsrv_free_stmt($stmt2);
        trace(" $numRows1 row".(($numRows1 > 1) ? "s" : " ")." $cond.\n");

        if ($numRows1 != $delRows) {
            die("Unexpected row count at delete: $numRows1 instead of $delRows");
        }
        $row += $numRows1;
    }

    $stmt3 = executeQuery($conn1, "DELETE TOP(1) FROM [$tableName]");
    $numRows2 = sqlsrv_rows_affected($stmt3);
    sqlsrv_free_stmt($stmt3);

    if ($numRows2 > 0) {
        die("Unexpected row count at delete: $numRows2");
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    deleteQuery();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Statement - Delete Query" completed successfully.
