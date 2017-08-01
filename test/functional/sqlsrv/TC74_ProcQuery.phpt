--TEST--
Stored Proc Query Test
--DESCRIPTION--
Verifies the data retrieved via a store procedure to validate behavior
of queries including SQLSRV_PARAM_OUT qualifiers.
Checks all numeric data types (i.e. 10 SQL types).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ProcQuery($minType, $maxType)
{
    include 'MsSetup.inc';

    $testName = "Stored Proc Query";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    for ($k = $minType; $k <= $maxType; $k++)
    {
        switch ($k)
        {
        case 1: // TINYINT
            ExecProcQuery($conn1, $procName, "TINYINT", 11, 12, 23);
            break;

        case 2: // SMALLINT
            ExecProcQuery($conn1, $procName, "SMALLINT", 4.3, 5.5, 9);
            break;

        case 3: // INT
            ExecProcQuery($conn1, $procName, "INT", 3.2, 4, 7);
            break;

        case 4: // BIGINT
            ExecProcQuery($conn1, $procName, "BIGINT", 5.2, 3.7, 8);
            break;

        case 5: // FLOAT
            ExecProcQuery($conn1, $procName, "FLOAT", 2.5, 5.25, 7.75);
            break;

        case 6: // REAL
            ExecProcQuery($conn1, $procName, "REAL", 3.4, 6.6, 10);
            break;

        case 7: // DECIMAL
            ExecProcQuery($conn1, $procName, "DECIMAL", 2.1, 5.3, 7);
            break;

        case 8: // NUMERIC
            ExecProcQuery($conn1, $procName, "NUMERIC", 2.8, 5.4, 8);
            break;

        case 9: // SMALLMONEY
            ExecProcQuery($conn1, $procName, "SMALLMONEY", 10, 11.7, 21.7);
            break;

        case 10:// MONEY
            ExecProcQuery($conn1, $procName, "MONEY", 22.3, 16.1, 38.4);
            break;

        default:// default
            break;
        }
    }   

    sqlsrv_close($conn1);

    EndTest($testName);
    
}

function ExecProcQuery($conn, $procName, $dataType, $inData1, $inData2, $outData)
{
    $procArgs = "@p1 $dataType, @p2 $dataType, @p3 $dataType OUTPUT";
    $procCode = "SELECT @p3 = CONVERT($dataType, @p1 + @p2)";
    CreateProc($conn, $procName, $procArgs, $procCode);

    $callArgs = "?, ?, ?";
    $callResult = 0.0;
    $callValues = array($inData1, $inData2, array(&$callResult, SQLSRV_PARAM_OUT));
    CallProc($conn, $procName, $callArgs, $callValues);
    DropProc($conn, $procName);

    TraceData($dataType, "".$inData1." + ".$inData2." = ".$callResult);
    if ($callResult != $outData)
    {
        die("Expected result for ".$dataType." was ".$outData);
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
        ProcQuery(1, 10);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Stored Proc Query" completed successfully.

