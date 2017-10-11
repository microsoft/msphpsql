--TEST--
Test various Katmai types, like geography, geometry, hierarchy, sparse, etc. and fetch them back as strings
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function Katmai_Basic_Types($conn)
{
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_time] time, [c2_date] date, [c3_datetimeoffset] datetimeoffset, [c4_geography] geography, [c5_geometry] geometry, [c6_hierarchyid] hierarchyid, [c7_uniqueidentifier] uniqueidentifier ROWGUIDCOL NOT NULL UNIQUE DEFAULT NEWID())");
    sqlsrv_free_stmt($stmt);

    $query = "INSERT INTO $tableName ([c1_time], [c2_date], [c3_datetimeoffset], [c4_geography], [c5_geometry], [c6_hierarchyid], [c7_uniqueidentifier]) VALUES ((''), ('0001-01-01'), (null), ('POINT(6.60 52.15)'), ('POLYGON((25.68 55.08, 26.03 55.99, 26.13 56.54, 25.68 55.08))'), ('/'), ('b644da01-2a9a-43b9-b98c-27c25a28cc5e'))";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    echo "\nShowing results of Katmai basic fields\n";
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $numFields = sqlsrv_num_fields($stmt);

    $result = sqlsrv_fetch($stmt);
    for ($i = 0; $i < $numFields; $i++) {
        if ($i < 3) {
            $value = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_DATETIME);
            if ($value == null) {
                if ($i != 2) {    // only this field is null
                    echo "$value is unexpected for field " . ($i+1) . "\n";
                }
            } else {
                if ($i == 0) {
                    $value = date('Y-m-d', date_format($value, "U"));
                    $today = date('Y-m-d');
                    if ($value !== $today) {
                        echo "$value is unexpected for field " . ($i+1) . "\n";
                    }
                } else {
                    $value = date_format($value, 'Y-m-d');
                    if ($value !== '0001-01-01') {
                        echo "$value is unexpected for field " . ($i+1) . "\n";
                    }
                }
            }
        } else {
            $value = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            var_dump($value);
        }
    }
    sqlsrv_free_stmt($stmt);
}

function Katmai_SparseChar($conn)
{
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (c1 int, c2 char(512) SPARSE NULL, c3 varchar(512) SPARSE NULL, c4 varchar(max) SPARSE NULL, c5 nchar(512) SPARSE NULL, c6 nvarchar(512) SPARSE NULL, c7 nvarchar(max) SPARSE NULL)");
    sqlsrv_free_stmt($stmt);

    $input = "The quick brown fox jumps over the lazy dog";
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1, c2, c5) VALUES(1, 'The quick brown fox jumps over the lazy dog', 'The quick brown fox jumps over the lazy dog')");
    sqlsrv_free_stmt($stmt);
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1, c3, c6) VALUES(2, 'The quick brown fox jumps over the lazy dog', 'The quick brown fox jumps over the lazy dog')");
    sqlsrv_free_stmt($stmt);
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1, c4, c7) VALUES(3, 'The quick brown fox jumps over the lazy dog', 'The quick brown fox jumps over the lazy dog')");
    sqlsrv_free_stmt($stmt);

    echo "\nComparing results of Katmai sparse fields\n";
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");

    while ($result = sqlsrv_fetch($stmt)) {
        $c1 = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $fld1 = $c1;
        $fld2 = $fld1 + 3;

        $value1 = sqlsrv_get_field($stmt, $fld1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $value2 = sqlsrv_get_field($stmt, $fld2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        if ($input !== trim($value1)) {
            echo "The value is unexpected!\n";
        }
        if ($value1 !== $value2) {
            echo "The values don't match!\n";
        }
    }
    sqlsrv_free_stmt($stmt);
}

function Katmai_SparseNumeric($conn)
{
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (c1 int, c2 int SPARSE NULL, c3 tinyint SPARSE NULL, c4 smallint SPARSE NULL, c5 bigint SPARSE NULL, c6 bit SPARSE NULL, c7 float SPARSE NULL, c8 real SPARSE NULL, c9 decimal(28,4) SPARSE NULL, c10 numeric(32,4) SPARSE NULL)");

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1, c2, c3, c4, c5, c6, c7, c8, c9, c10) VALUES(1, '1', '1', '1', '1', '1', '1', '1', '1', '1')");
    sqlsrv_free_stmt($stmt);

    echo "\nShowing results of Katmai sparse numeric fields\n";
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $numFields = sqlsrv_num_fields($stmt);
    $result = sqlsrv_fetch($stmt);

    for ($i = 1; $i < $numFields; $i++) {
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
    startTest("sqlsrv_katmai_special_types");
    echo "\nTest begins...\n";
    try {
        set_time_limit(0);
        sqlsrv_configure('WarningsReturnAsErrors', 1);

        // Connect
        $conn = Connect();
        if (!$conn) {
            fatalError("Could not connect.\n");
        }

        Katmai_Basic_Types($conn);
        Katmai_SparseChar($conn);
        Katmai_SparseNumeric($conn);

        sqlsrv_close($conn);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_katmai_special_types");
}

RunTest();

?>
--EXPECT--
﻿﻿
Test begins...

Showing results of Katmai basic fields
string(44) "E6100000010C3333333333134A406666666666661A40"
string(192) "00000000010404000000AE47E17A14AE39400AD7A3703D8A4B4048E17A14AE073A401F85EB51B8FE4B40E17A14AE47213A4085EB51B81E454C40AE47E17A14AE39400AD7A3703D8A4B4001000000020000000001000000FFFFFFFF0000000003"
string(0) ""
string(36) "B644DA01-2A9A-43B9-B98C-27C25A28CC5E"

Comparing results of Katmai sparse fields

Showing results of Katmai sparse numeric fields
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(3) "1.0"
string(3) "1.0"
string(6) "1.0000"
string(6) "1.0000"

Done
Test "sqlsrv_katmai_special_types" completed successfully.
