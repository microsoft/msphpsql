--TEST--
Populate different binary fields using null stream data as inputs.
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function NullStream_Bin2String($conn, $tableName)
{
    $fname = null;
    $value = -2106133115;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varbinary, c3_varbinary_max, c4_image) VALUES (?, ?, ?, ?)", array($value, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512)), array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')), array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    sqlsrv_free_stmt($stmt);

    FetchData($conn, $tableName, $value);
}

function NullStreamPrep_Bin2String($conn, $tableName)
{
    $fname = null;
    $value = -413736480;
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_varbinary, c3_varbinary_max, c4_image) VALUES (?, ?, ?, ?)", array($value, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512)), array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')), array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);

    FetchData($conn, $tableName, $value);
}

function FetchData($conn, $tableName, $value)
{
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName WHERE c1_int = $value");
    $result = sqlsrv_fetch($stmt);
    $numfields = sqlsrv_num_fields($stmt);
    for ($i = 1; $i < $numfields; $i++) {
        $value = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        var_dump($value);
    }
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    startTest("sqlsrv_streams_null_binary");
    echo "\nTest begins...\n";
    try {
        set_time_limit(0);
        sqlsrv_configure('WarningsReturnAsErrors', 1);

        // connect
        $conn = connect();
        if (!$conn) {
            fatalError("Could not connect.\n");
        }

        // create a test table
        $tableName = GetTempTableName();
        $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary] varbinary(512), [c3_varbinary_max] varbinary(max), [c4_image] image)");
        sqlsrv_free_stmt($stmt);

        NullStream_Bin2String($conn, $tableName);
        NullStreamPrep_Bin2String($conn, $tableName);

        sqlsrv_close($conn);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_streams_null_binary");
}

RunTest();

?>
--EXPECT--
﻿﻿
Test begins...
NULL
NULL
NULL
NULL
NULL
NULL

Done
Test "sqlsrv_streams_null_binary" completed successfully.
