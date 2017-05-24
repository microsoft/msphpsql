--TEST--
Fetch Field Test
--DESCRIPTION--
Verifies the ability to successfully retrieve field data via "sqlsrv_get_field" by
retrieving fields from a table including rows with all supported SQL types (28 types).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function FetchFields()
{
    include 'MsSetup.inc';

    $testName = "Fetch - Field";
    StartTest($testName);

    Setup();
    if (! IsWindows())
            $conn1 = ConnectUTF8();
    else 
        $conn1 = Connect();
        
    CreateTable($conn1, $tableName);

    $noRows = 10;
    $noRowsInserted = InsertRows($conn1, $tableName, $noRows);

    $stmt1 = SelectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);

    Trace("Retrieving $noRowsInserted rows with $numFields fields each ...");
    for ($i = 0; $i < $noRowsInserted; $i++)
    {
        $row = sqlsrv_fetch($stmt1);
        if ($row === false)
        {
            FatalError("Row $i is missing");
        }
        for ($j = 0; $j < $numFields; $j++)
        {
            $fld = sqlsrv_get_field($stmt1, $j);
            if ($fld === false)
            {
                FatalError("Field $j of Row $i is missing\n");
            }

        }
    }
    sqlsrv_free_stmt($stmt1);
    Trace(" completed successfully.\n");

    DropTable($conn1, $tableName);	
    
    sqlsrv_close($conn1);

    EndTest($testName);	
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        FetchFields();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Fetch - Field" completed successfully.
