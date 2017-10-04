--TEST--
Test sending queries (query or prepare) with a timeout specified using transactions. Errors are expected.
--FILE--
﻿<?php
require_once('MsCommon.inc');

function QueryTimeout($conn1, $conn2, $commit)
{
    $tableName = GetTempTableName('testQueryTimeout', false);

    $stmt = sqlsrv_query($conn1, "CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney)");
    sqlsrv_free_stmt($stmt);
    sqlsrv_begin_transaction($conn1);

    $query = "INSERT INTO $tableName VALUES ((-2147483648), (127), (null), (9223372036854775807), (0), (1), (0), (null), (0.8654), (922337203685477.5807), (-214748.3648))";
    $stmt = sqlsrv_query($conn1, $query);
    $numRows = sqlsrv_rows_affected($stmt);
    if ($numRows !== 1) {
        echo "Number of rows affected unexpected!\n";
    }

    $query = "INSERT INTO $tableName VALUES ((2147483647), (154), (-5459), (1), (0), (-1.79E+308), (1), (0.4430), (0), (0.2511), (0.7570))";
    $stmt = sqlsrv_query($conn1, $query);
    $numRows = sqlsrv_rows_affected($stmt);
    if ($numRows !== 1) {
        echo "Number of rows affected unexpected!\n";
    }
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn2, "WAITFOR DELAY '00:00:03'; SELECT * FROM $tableName", array(), array('QueryTimeout' => 1));
    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
    $e = $errors[0];

    print($e['message'] . "\n");
    print($e['code'] . "\n");
    print($e['SQLSTATE'] . "\n");

    if ($commit) {
        sqlsrv_commit($conn1);
    } else {
        sqlsrv_rollback($conn1);
    }

    sqlsrv_query($conn2, "DROP TABLE $tableName");
}

function Repro()
{
    startTest("sqlsrv_statement_query_timeout_transaction");
    echo "\nTest begins...\n";
    try {
        set_time_limit(0);
        sqlsrv_configure('WarningsReturnAsErrors', 1);

        // connect
        $conn1 = connect(array('ConnectionPooling'=>0));
        if (!$conn1) {
            fatalError("Could not connect.\n");
        }

        $conn2 = connect(array('ConnectionPooling'=>0));
        if (!$conn2) {
            fatalError("Could not connect.\n");
        }

        QueryTimeout($conn1, $conn2, true);
        QueryTimeout($conn1, $conn2, false);

        sqlsrv_close($conn1);
        sqlsrv_close($conn2);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_statement_query_timeout_transaction");
}

Repro();

?>
--EXPECTREGEX--
﻿
Test begins...
\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired
0
HYT00
\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired
0
HYT00

Done
Test \"sqlsrv_statement_query_timeout_transaction\" completed successfully\.
