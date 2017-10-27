--TEST--
Complex Insert Query Test
--DESCRIPTION--
Test the driver behavior with a complex insert query.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function complexInsert($count)
{
    $testName = "Complex Insert Query";

    startTest($testName);

    setup();

    $conn1 = AE\connect();

    $tableName = 'TC83test';
    dropTable($conn1, $tableName);

    $data = "a1='1', a2='2', a3='3', a4='4', a5='5', a6='6'";
    $querySelect = "SELECT COUNT(*) FROM [$tableName]";
    $queryInsert =
    "   SELECT $data INTO [$tableName]
        DECLARE @i int
        SET @i=1
        WHILE (@i < $count)
        BEGIN
            INSERT [$tableName]
            SELECT $data
            SET @i = @i + 1
        END
    ";

    $stmt1 = executeQuery($conn1, $queryInsert);
    while (sqlsrv_next_result($stmt1) != null) {
    };
    sqlsrv_free_stmt($stmt1);

    $stmt2 = executeQuery($conn1, $querySelect);
    $row = sqlsrv_fetch_array($stmt2);
    sqlsrv_free_stmt($stmt2);

    printf("$count rows attempted; actual rows created = ".$row[0]."\n");

    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    complexInsert(160);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
160 rows attempted; actual rows created = 160
Test "Complex Insert Query" completed successfully.
