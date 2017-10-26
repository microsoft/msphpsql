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
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function FetchMetadata()
{
    include 'MsSetup.inc';

    $testName = "Fetch - Metadata";
    startTest($testName);

    setup();
    $conn1 = connect();
    createTable($conn1, $tableName);

    insertRow($conn1, $tableName);


    $stmt1 = selectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);

    trace("Expecting $numFields fields...\n");

    $metadata = sqlsrv_field_metadata($stmt1);
    $count = count($metadata);
    if ($count != $numFields) {
        die("Unexpected metadata size: ".$count);
    }
    for ($k = 0; $k < $count; $k++) {
        trace(" ".($k + 1)."\t");
        ShowMetadata($metadata, $k, 'Name');
        ShowMetadata($metadata, $k, 'Size');
        ShowMetadata($metadata, $k, 'Precision');
        ShowMetadata($metadata, $k, 'Scale');
        ShowMetadata($metadata, $k, 'Nullable');
        trace("\n");
    }

    sqlsrv_free_stmt($stmt1);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function ShowMetadata($mdArray, $field, $info)
{
    $mdInfo = $mdArray[$field][$info];
    $refInfo = GetMetadata($field + 1, $info);
    trace("[$info=$mdInfo]");
    if ($mdInfo != $refInfo) {
        die("Unexpected metadata value for $info in field ".($field + 1).": $mdInfo instead of $refInfo");
    }
}

//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        FetchMetadata();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test "Fetch - Metadata" completed successfully.
