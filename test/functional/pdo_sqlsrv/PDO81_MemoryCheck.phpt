--TEST--
Memory Leakage Test
--DESCRIPTION--
Checks for memory leaks using memory_get_usage(). memory_get_usage() only tracks the memory that is allocated using
emalloc (which only allocate memory in the memory space allocated for the PHP process).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function MemCheck($noPasses, $noRows1, $noRows2, $startStep, $endStep, $leakThreshold)
{
    include 'MsSetup.inc';

    $testName = "Memory Leakage Check";

    StartTest($testName);

    Setup();

    Trace("Execution setup: $noPasses passes over a table with $noRows1 => ".($noRows1 + $noRows2)." rows.\n");
    $conn1 = Connect();

    CreateTable($conn1, $tableName);
    $noRowsInserted = InsertRows($conn1, $tableName, $noRows1);

    // Calibration
    $phpLeak =  RunTest($noPasses, 0, $tableName, $conn1, false, 0);
    Trace("\n0. Calibration\t - PHP memory leak: $phpLeak bytes\n");

    // Preliminary Execution
    Trace("\nPreliminary Execution:\n");
    $drvLeak = 0;
    for ($j = 0; $j < 2; $j++)
    {
        $leak = ExecTest(1, $noRows1, $startStep, $endStep, $tableName, $conn1, (($j % 2) != 0), $phpLeak);
        if ($leak > $drvLeak)
        {
            $drvLeak = $leak;
        }
    }
    $totalLeak = 0;

    // Execution
    $noRows = $noRows1;
    $prepared = false;
    Trace("\nActual Execution:\n");
    for ($j = 0; $j < 4; $j++)
    {
        switch ($j)
        {
            case 0:
                $prepared = false;
                break;

            case 1:
                $prepared = false;
                InsertRows($conn1, $tableName, $noRows2);
                $noRows += $noRows2;
                break;

            case 2:
                $prepared = true;
                break;

            case 3:
                $prepared = true;
                InsertRows($conn1, $tableName, $noRows2);
                $noRows += $noRows2;
                break;

            default:
                break;

        }
        $leak = ExecTest($noPasses, $noRows, $startStep, $endStep, $tableName, $conn1, $prepared, $phpLeak) - $drvLeak;
        if ($leak > $totalLeak)
        {
            $totalLeak = $leak;
        }
    }

    $conn1 = null;

    $conn2 = Connect();
    DropTable($conn2, $tableName);    
    $conn2 = null;

    if ($totalLeak > 0)
    {
        $expectedLeak = min($drvLeak, $leakThreshold)  * $noPasses;
        Trace("Driver memory leak: $totalLeak bytes (max expected: $expectedLeak)\n");
        if ($totalLeak > $expectedLeak)
        {
            die("Memory leaks detected: $totalLeak bytes\n");
        }
    }

    EndTest($testName);    
}

function ExecTest($noPasses, $noRows, $startStep, $endStep, $tableName, $conn, $prepared, $phpLeak)
{
    $leak = 0;

    // Execution
    if ($prepared)
    {
        Trace("\nPrepared Query Mode\n");
    }
    else
    {
        Trace("\nDirect Query Mode\n");
    }
    for ($i = $startStep; $i <= $endStep; $i++)
    {
        switch ($i)
        {
            case 0:    // Calibration
                Trace("$i. Calibration\t - ");
                break;

            case 1:    // connection only
                Trace("$i. Connection\t - ");
                break;

            case 2:    // query
                Trace("$i. Query\t - ");
                break;

            case 3:    // fetch
                Trace("$i. Fetch\t - ");
                break;

            default:
                break;
        }

        $memLeak = RunTest($noPasses, $noRows, $tableName, $conn, $prepared, $i) - $phpLeak;
        Trace("Driver memory leak: $memLeak bytes\n");
        if ($memLeak > $leak)
        {
            $leak = $memLeak;
        }
    }

    return ($leak);
}

function RunTest($noPasses, $noRows, $tableName, $conn, $prepared, $mode)
{
    $leak = 0;
    for ($k = 1; $k <= $noPasses; $k++)
    {
        $tsql = "SELECT * FROM [$tableName]";
        $memStart = 0;
        $memEnd = 0;
        $conn2 = null;
        $stmt = null;
        $row = null;
        $rowCount = 0;
        $fldCount = 0;

        $memStart =  memory_get_usage();
        switch ($mode)
        {
            case 0:    // calibration
                break;

            case 1:    // connection
                $conn2 = GetConnection();
                unset($conn2);
                break;

            case 2:    // query
                $stmt = ExecuteQueryEx($conn, $tsql, ($prepared ? false : true));
                $fldCount = $stmt->columnCount();
                $stmt->closeCursor();
                unset($stmt);
                break;

            case 3:    // fetch
                $stmt = ExecuteQueryEx($conn, $tsql, ($prepared ? false : true));
                $fldCount = $stmt->columnCount();
                while ($row = $stmt->fetch())
                {
                    unset($row);
                    $rowCount++;
                }
                $stmt->closeCursor();
                unset($stmt);
                if ($rowCount != $noRows)
                {
                    die("$rowCount rows retrieved instead of $noRows\n");
                }
                break;

            default:
                break;

        }
        $memEnd =  memory_get_usage();
        if ($memEnd > $memStart)
        {
            $leak += ($memEnd - $memStart);
        }

    }
    return ($leak);
}

function GetConnection()
{
    include 'MsSetup.inc';
    $conn = PDOConnect('PDO', $server, $uid, $pwd, true);
        return ($conn);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        MemCheck(20, 10, 15, 1, 3, 0);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Memory Leakage Check" completed successfully.
