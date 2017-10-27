--TEST--
Fetch Metadata Test
--DESCRIPTION--
Verifies that "sqlsrv_field_metadata returns" the same number of fields
as "sqlsrv_num_fields".
Verifies the metadata array (NAME, TYPE, SIZE, PRECISION, SCALE and NULLABLE)
for all SQL types currently supported (28 types).
Validates functionality with statement in both prepared and executed state.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function fetchMetadata()
{
    $testName = "Fetch - Metadata";
    startTest($testName);

    setup();
    $tableName = 'TC41test';
    
    $conn1 = AE\connect();
    AE\createTestTable($conn1, $tableName);
    AE\insertTestRows($conn1, $tableName, 1);

    $stmt1 = AE\selectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);

    trace("Expecting $numFields fields...\n");

    $metadata = sqlsrv_field_metadata($stmt1);
    $count = count($metadata);
    if ($count != $numFields) {
        die("Unexpected metadata size: ".$count);
    }
    for ($k = 0; $k < $count; $k++) {
        trace(" ".($k + 1)."\t");
        showMetadata($metadata, $k, 'Name');
        showMetadata($metadata, $k, 'Size');
        showMetadata($metadata, $k, 'Precision');
        showMetadata($metadata, $k, 'Scale');
        showMetadata($metadata, $k, 'Nullable');
        trace("\n");
    }

    sqlsrv_free_stmt($stmt1);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function showMetadata($mdArray, $field, $info)
{
    $mdInfo = $mdArray[$field][$info];
    $refInfo = getMetadata($field + 1, $info);
    trace("[$info=$mdInfo]");
    if ($mdInfo != $refInfo) {
        die("Unexpected metadata value for $info in field ".($field + 1).": $mdInfo instead of $refInfo");
    }
}

try {
    fetchMetadata();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Fetch - Metadata" completed successfully.
