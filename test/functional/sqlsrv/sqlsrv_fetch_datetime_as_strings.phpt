--TEST--
Test fetching datatime fields as strings
--FILE--
﻿<?php
require_once('MsCommon.inc');

function FetchDateTime_AsString($conn)
{
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_timestamp] timestamp, [c3_datetime] datetime, [c4_smalldatetime] smalldatetime)");
    sqlsrv_free_stmt($stmt);

    $numRows = 0;
    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $query = "SELECT [c3_datetime], [c4_smalldatetime] FROM $tableName ORDER BY c2_timestamp";
    $stmt1 = sqlsrv_query($conn, $query);
    $stmt2 = sqlsrv_query($conn, $query);

    FetchData($stmt1, $stmt2, $numRows);

    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
}

function FetchData($stmt1, $stmt2, $numRows)
{
    $rowFetched = 0;
    do {
        $obj = sqlsrv_fetch_object($stmt1);
        $row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);

        $value1 = $obj->c3_datetime;
        $value2 = $row['c3_datetime'];

        if ($value1 !== $value2) {
            echo "Data corrupted: $value1 !== $value2\n";
        }

        $value1 = $obj->c4_smalldatetime;
        $value2 = $row['c4_smalldatetime'];

        if ($value1 !== $value2) {
            echo "Data corrupted: $value1 !== $value2\n";
        }
    } while (++$rowFetched < $numRows);
}

function GetQuery($tableName, $index)
{
    $query = "";
    switch ($index) {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c3_datetime], [c4_smalldatetime]) VALUES ((2073189157), ('1753-01-01 00:00:00.000'), (null))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c3_datetime], [c4_smalldatetime]) VALUES ((-920147222), ('3895-08-29 00:41:03.351'), ('1936-01-05 21:34:00'))";
            break;
        case 3:
            $query = "INSERT INTO $tableName ([c1_int], [c3_datetime], [c4_smalldatetime]) VALUES ((-2147483648), ('1753-01-01 00:00:00.000'), ('1915-11-08 19:46:00'))";
            break;
        case 4:
            $query = "INSERT INTO $tableName ([c1_int], [c3_datetime], [c4_smalldatetime]) VALUES ((1269199053), (null), ('2075-04-27 22:16:00'))";
            break;
        default:
            break;
    }
    return $query;
}

function Repro()
{
    startTest("sqlsrv_fetch_datetime_as_strings");
    try {
        set_time_limit(0);
        sqlsrv_configure('WarningsReturnAsErrors', 1);
        echo "\nTest begins...\n";

        // Connect
        $conn = connect(array('ReturnDatesAsStrings'=>true));
        if (!$conn) {
            fatalError("Could not connect.\n");
        }

        FetchDateTime_AsString($conn);

        sqlsrv_close($conn);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_fetch_datetime_as_strings");
}

Repro();

?>
--EXPECT--
﻿
Test begins...

Done
Test "sqlsrv_fetch_datetime_as_strings" completed successfully.
