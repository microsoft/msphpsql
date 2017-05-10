--TEST--
Fetch Next Result Test
--DESCRIPTION--
Verifies the functionality of “sqlsvr_next_result”
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

    $testName = "Fetch - Next Result";
    StartTest($testName);

    if (!IsMarsSupported())
    {
        EndTest($testName);	
        return;
    }

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    $noRows = 10;
    InsertRows($conn1, $tableName, $noRows);

    $stmt1 = SelectQuery($conn1, "SELECT * FROM [$tableName]");
    $stmt2 = SelectQuery($conn1, "SELECT * FROM [$tableName]; SELECT * FROM [$tableName]");
    if (sqlsrv_next_result($stmt2) === false)
    {
        FatalError("Failed to retrieve next result set");
    }

    $numFields1 = sqlsrv_num_fields($stmt1);
    $numFields2 = sqlsrv_num_fields($stmt2);
    if ($numFields1 != $numFields2)
    {
        SetUTF8Data(false);
        die("Unexpected number of fields: $numField1 => $numFields2");
    }

    Trace("Retrieving $noRows rows with $numFields1 fields each ...");
    for ($i = 0; $i < $noRows; $i++)
    {
        $row1 = sqlsrv_fetch($stmt1);
        $row2 = sqlsrv_fetch($stmt2);
        if (($row1 === false) || ($row2 === false))
        {
            FatalError("Row $i is missing");
        }
        for ($j = 0; $j < $numFields1; $j++)
        {   
            if (UseUTF8Data()){
                $fld1 = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
                $fld2 = sqlsrv_get_field($stmt2, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
            }
            else {
                $fld1 = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
                $fld2 = sqlsrv_get_field($stmt2, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }
            if (($fld1 === false) || ($fld2 === false))
            {
                FatalError("Field $j of Row $i is missing");
            }
            if ($fld1 != $fld2)
            {
                SetUTF8Data(false);
                die("Data corruption on row ".($i + 1)." column ".($j + 1)." $fld1 => $fld2");
            }

        }
    }
    if (sqlsrv_next_result($stmt1) ||
        sqlsrv_next_result($stmt2))
    {
        SetUTF8Data(false);
        die("No more results were expected");
    }
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
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
    if (! IsWindows())
    {
        SetUTF8Data(true);
    }
    
    try
    {
        FetchFields();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    SetUTF8Data(false);
}

Repro();

?>
--EXPECT--
Test "Fetch - Next Result" completed successfully.
