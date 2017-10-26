--TEST--
Test calling sqlsrv_get_field twice in a row. Intentionally trigger various error messages.
--FILE--
﻿<?php
require_once('MsCommon.inc');

function FetchFieldTwice($conn)
{
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_float] float, [c3_real] real, [c4_datetime] datetime)");
    sqlsrv_free_stmt($stmt);

    $query = "INSERT INTO $tableName ([c1_int], [c2_float], [c3_real], [c4_datetime]) VALUES ((968580013), (1.09), (3.438), ('1756-04-16 23:27:09.131'))";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_execute($stmt);
    PrintError();   // errors expected here

    sqlsrv_free_stmt($stmt);

    $query = "SELECT * FROM $tableName";
    $stmt = sqlsrv_prepare($conn, $query);
    $result = sqlsrv_fetch($stmt);
    if ($result !== false) {
        echo "Fetch should have failed!\n";
    }
    PrintError();   // errors expected here

    if (! sqlsrv_execute($stmt)) {
        fatalError("Errors in executing statement.\n");
    }

    $numFields = sqlsrv_num_fields($stmt);
    $metadata = sqlsrv_field_metadata($stmt);
    while ($result = sqlsrv_fetch($stmt)) {
        for ($i = -1; $i <= $numFields; $i++) {
            FetchField($stmt, $i, $numFields, false);
            FetchField($stmt, $i, $numFields, true);
        }
    }

    sqlsrv_free_stmt($stmt);
}

function FetchField($stmt, $idx, $numFields, $errorExpected)
{
    if ($idx < 0 || $idx >= $numFields) {
        $value1 = sqlsrv_get_field($stmt, $idx);
        PrintError(true);   // errors expected because $idx is out of bound
    } else {
        if ($idx == 3) {
            $value1 = sqlsrv_get_field($stmt, $idx, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        } else {
            $value1 = sqlsrv_get_field($stmt, $idx);
        }
        var_dump($value1);

        PrintError($errorExpected);
    }
}

function PrintError($errorExpected = true)
{
    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
    if (!empty($errors)) {
        $e = $errors[0];
        var_dump($e['message']);
    } elseif ($errorExpected) {
        echo "An error is expected!\n";
    }
}

function Repro()
{
    startTest("sqlsrv_fetch_field_twice_data_types");
    try {
        set_time_limit(0);
        sqlsrv_configure('WarningsReturnAsErrors', 1);

        echo "\nTest begins...\n";

        // Connect
        $conn = connect();
        if (!$conn) {
            fatalError("Could not connect.\n");
        }

        FetchFieldTwice($conn);

        sqlsrv_close($conn);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_fetch_field_twice_data_types");
}

Repro();

?>
--EXPECT--
﻿
Test begins...
string(79) "A statement must be prepared with sqlsrv_prepare before calling sqlsrv_execute."
string(63) "The statement must be executed before results can be retrieved."
string(52) "An invalid parameter was passed to sqlsrv_get_field."
string(52) "An invalid parameter was passed to sqlsrv_get_field."
int(968580013)
bool(false)
string(25) "Field 0 returned no data."
float(1.09)
bool(false)
string(25) "Field 1 returned no data."
float(3.4379999637604)
bool(false)
string(25) "Field 2 returned no data."
string(23) "1756-04-16 23:27:09.130"
bool(false)
string(25) "Field 3 returned no data."
string(52) "An invalid parameter was passed to sqlsrv_get_field."
string(52) "An invalid parameter was passed to sqlsrv_get_field."

Done
Test "sqlsrv_fetch_field_twice_data_types" completed successfully.
