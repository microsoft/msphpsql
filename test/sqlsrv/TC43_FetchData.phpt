--TEST--
Fetch Field Data Test verifies the data retrieved via "sqlsrv_get_field"
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

    $testName = "Fetch - Field Data";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    $startRow = 1;
    $noRows = 20;
    InsertRowsByRange($conn1, $tableName, $startRow, $startRow + $noRows - 1);

    $query = "SELECT * FROM [$tableName] ORDER BY c27_timestamp";
    $stmt1 = SelectQuery($conn1, $query);
    $numFields = sqlsrv_num_fields($stmt1);

    Trace("Retrieving $noRows rows with $numFields fields each ...");
    for ($i = 0; $i < $noRows; $i++)
    {
        $row = sqlsrv_fetch($stmt1);
        if ($row === false)
        {
            FatalError("Row $i is missing");
        }
        $skipCount = 0;
        for ($j = 0; $j < $numFields; $j++)
        {
            if (UseUTF8Data()){
                $fld = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
            }
            else{
                $fld = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }
            if ($fld === false)
            {
                FatalError("Field $j of Row $i is missing");
            }
            $col = $j + 1;
            if (!IsUpdatable($col))
            {
                $skipCount++;
            }
            else // should check data even if $fld is null
            {
                $data = GetInsertData($startRow + $i, $col, $skipCount);
                if (!CheckData($col, $fld, $data))
                {
                    SetUTF8Data(false);
                    die("Data corruption on row ".($startRow + $i)." column $col");
                }
            }
        }
    }
    sqlsrv_free_stmt($stmt1);
    Trace(" completed successfully.\n");

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName); 
}


function CheckData($col, $actual, $expected)
{
    $success = true;

    if (IsNumeric($col))
    {
        if (floatval($actual) != floatval($expected))
        {
            $success = false;
        }
    }
    else
    {
        $actual = trim($actual);
        $len = strlen($expected);
        if (IsDateTime($col))
        {
            $len = min(strlen("YYYY-MM-DD HH:mm:ss"), $len);
        }
        if (strncasecmp($actual, $expected, $len) != 0)
        {
            $success = false;
        }
    }
    if (!$success)
    {
        Trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }

    return ($success);
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
Test "Fetch - Field Data" completed successfully.
