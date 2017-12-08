--TEST--
Test various Katmai types, like geography, geometry, hierarchy, sparse, etc. and fetch them back as strings
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
﻿﻿<?php
require_once("MsCommon_mid-refactor.inc");

function katmaiBasicTypes($conn)
{
    $tableName = getTableName();
    $dataTypes = array("c1_time" => "time",
                       "c2_date" => "date",
                       "c3_datetimeoffset" => "datetimeoffset",
                       "c4_geography" => "geography",
                       "c5_geometry" => "geometry",
                       "c6_hierarchyid" => "hierarchyid",
                       new ColumnMeta("uniqueidentifier", "c7_uniqueidentifier", "ROWGUIDCOL NOT NULL UNIQUE DEFAULT NEWID()"));
    $data = array("c1_time" => '03:32:25.5643401',
                  "c2_date" => '1439-01-10',
                  "c3_datetimeoffset" => '0221-01-12 06:39:07.0620256+00:00',
                  "c4_geography" => 'POINT(27.91 -76.74)',
                  "c5_geometry" => 'LINESTRING(30.50 -0.66, 31.03 -0.38)',
                  "c6_hierarchyid" => '/1/3/',
                  "c7_uniqueidentifier" => '5a1a88f7-3749-46a3-8a7a-efae73efe88f');
    $expOutput = array("c1_time" => '03:32:25.5643401',
                  "c2_date" => '1439-01-10',
                  "c3_datetimeoffset" => '0221-01-12 06:39:07.0620256 +00:00',
                  "c4_geography" => 'e6100000010c8fc2f5285c2f53c0295c8fc2f5e83b40',
                  "c5_geometry" => '0000000001140000000000803e401f85eb51b81ee5bf48e17a14ae073f4052b81e85eb51d8bf',
                  "c6_hierarchyid" => '5bc0',
                  "c7_uniqueidentifier" => '35413141383846372d333734392d343641332d384137412d454641453733454645383846');
                  
    if (isColEncrypted()) {
        // remove these types from tests because these types require direct query for the data to be inserted
        // and the insertRow common function uses bind parameters to insertion when column encryption is enabled
        $toRemove = array("c4_geography", "c5_geometry", "c6_hierarchyid");
        foreach ($toRemove as $key) {
            unset($dataTypes[$key]);
            unset($data[$key]);
            unset($expOutput[$key]);
        }
    }
    $expOutput = array_values($expOutput);
    createTable($conn, $tableName, $dataTypes);
    insertRow($conn, $tableName, $data);
    echo "Comparing results of Katmai basic fields\n";

    $stmt = $conn->query("SELECT * FROM $tableName");
    $numFields = $stmt->columnCount();
    $cols = array_fill(0, $numFields, "");

    for ($i = 0; $i < $numFields; $i++) {
        $stmt->bindColumn($i+1, $cols[$i]);
    }

    $stmt->fetch(PDO::FETCH_BOUND);
    for ($i = 0; $i < $numFields; $i++) {
        $value = $cols[$i];
        if ($i >= 3) {
            if ($value != null) {
                $value = bin2hex($value);
            }
        }
        if ($value !== $expOutput[$i]) {
            echo "Unexpected output retrieved.\n";
            var_dump($value);
        }
    }
    dropTable($conn, $tableName);
}

function katmaiSparseChar($conn)
{
    $tableName = getTableName();

    // Sparse column set is not supported for Always Encrypted
    $options = "";
    if (!isColEncrypted()) {
        $options = "SPARSE NULL";
    }
    $dataTypes = array("c1" => "int",
                       new ColumnMeta("char(512)", "c2", $options),
                       new ColumnMeta("char(512)", "c3", $options),
                       new ColumnMeta("varchar(max)", "c4", $options),
                       new ColumnMeta("nchar(512)", "c5", $options),
                       new ColumnMeta("nvarchar(512)", "c6", $options),
                       new ColumnMeta("nvarchar(max)", "c7", $options));
    createTable($conn, $tableName, $dataTypes);

    $input = "The quick brown fox jumps over the lazy dog";
    insertRow($conn, $tableName, array("c1" => 1, "c2" => $input, "c5" => $input));
    insertRow($conn, $tableName, array("c1" => 2, "c3" => $input, "c6" => $input));
    insertRow($conn, $tableName, array("c1" => 3, "c4" => $input, "c7" => $input));

    echo "Comparing results of Katmai sparse fields\n";
    $stmt = $conn->query("SELECT * FROM $tableName");

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $fld1 = $row[0];
        $fld2 = $fld1 + 3;

        $value1 = $row[$fld1];
        $value2 = $row[$fld2];

        if ($input !== trim($value1)) {
            echo "The value is unexpected!\n";
        }
        // trimming is required since SPARSE is not supported for encrypted columns
        if (trim($value1) !== trim($value2)) {
            echo "The values don't match!\n";
        }
    }
    dropTable($conn, $tableName);
}

function katmaiSparseNumeric($conn)
{
    $tableName = getTableName();

    // Sparse column set is not supported for Always Encrypted
    $options = "";
    if (!isColEncrypted()) {
        $options = "SPARSE NULL";
    }

    $dataTypes = array("c1" => "int",
                       new ColumnMeta("int", "c2", $options),
                       new ColumnMeta("tinyint", "c3", $options),
                       new ColumnMeta("smallint", "c4", $options),
                       new ColumnMeta("bigint", "c5", $options),
                       new ColumnMeta("bit", "c6", $options),
                       new ColumnMeta("float", "c7", $options),
                       new ColumnMeta("real", "c8", $options),
                       new ColumnMeta("decimal(28,4)", "c9", $options),
                       new ColumnMeta("numeric(32,4)", "c10", $options));
    createTable($conn, $tableName, $dataTypes);

    $data = array("c1" => 1);
    for ($i = 1; $i < 10; $i++) {
        $colName = "c" . strval($i+1);
        $data[$colName] = '1';
    }
    insertRow($conn, $tableName, $data);

    echo "Showing results of Katmai sparse numeric fields\n";
    $stmt = $conn->query("SELECT * FROM $tableName");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    foreach ($row as $value) {
        var_dump($value);
    }
    dropTable($conn, $tableName);
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
try {
    $conn = connect();

    katmaiBasicTypes($conn);
    katmaiSparseChar($conn);
    katmaiSparseNumeric($conn);

    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
﻿﻿Comparing results of Katmai basic fields
Comparing results of Katmai sparse fields
Showing results of Katmai sparse numeric fields
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(3) "1.0"
string(3) "1.0"
string(6) "1.0000"
string(6) "1.0000"
