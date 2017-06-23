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
include 'MsCommon.inc';

function FetchMetadata()
{
    include 'MsSetup.inc';

    $testName = "Fetch - Metadata";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    InsertRow($conn1, $tableName);


    $stmt1 = SelectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);

    Trace("Expecting $numFields fields...\n");

    $metadata = sqlsrv_field_metadata($stmt1);
    $count = count($metadata);
    if ($count != $numFields)
    {
        die("Unexpected metadata size: ".$count);
    }
    for ($k = 0; $k < $count; $k++)
    {
        Trace(" ".($k + 1)."\t");
        ShowMetadata($metadata, $k, 'Name');
        ShowMetadata($metadata, $k, 'Size');
        ShowMetadata($metadata, $k, 'Precision');
        ShowMetadata($metadata, $k, 'Scale');
        ShowMetadata($metadata, $k, 'Nullable');
        Trace("\n");
    }

    sqlsrv_free_stmt($stmt1);

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName); 
}

function ShowMetadata($mdArray, $field, $info)
{
    $mdInfo = $mdArray[$field][$info];
    $refInfo = GetMetadata($field + 1, $info);
    Trace("[$info=$mdInfo]");
    if ($mdInfo != $refInfo)
    {
        die ("Unexpected metadata value for $info in field ".($field + 1).": $mdInfo instead of $refInfo");
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        FetchMetadata();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Fetch - Metadata" completed successfully.
