--TEST--
Memory Leakage Test
--DESCRIPTION--
Checks for memory leaks using memory_get_usage(). memory_get_usage() only tracks the memory that is allocated using
emalloc (which only allocate memory in the memory space allocated for the PHP process).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function memCheck($noPasses, $noRows1, $noRows2, $startStep, $endStep)
{
    $testName = "Memory Leakage Check";

    startTest($testName);

    setup();

    trace("Execution setup: $noPasses passes over a table with $noRows1 => ".($noRows1 + $noRows2)." rows.\n");

    // The data added into the table has some UTF-8 characters in it.
    // The fetch functions in the switch block below fail if we don't
    // set the encoding to UTF-8. We can set the UTF-8 option elsewhere
    // (in the options for sqlsrv_fetch for example) but it is easier
    // to simply call connect(array( 'CharacterSet'=>'UTF-8' )).
    $conn1 = AE\connect(array( 'CharacterSet'=>'UTF-8' ));
    setUTF8Data(true);

    $tableName = 'TC81test';
    AE\createTestTable($conn1, $tableName);
    $noRowsInserted = AE\insertTestRows($conn1, $tableName, $noRows1);

    // Calibration
    // when fetching DateTime in the test, the DateTime PHP extension is used, and memory is allocated when this
    // is extension is first used. Thus create a new DateTime and release it in the calibration step so it won't
    // appear to be a leak in the testing step.
    $date = new DateTime();
    unset($date);
    $phpLeak =  runTest($noPasses, 0, $tableName, $conn1, false, true, 0);
    trace("\n0. Calibration\t - PHP memory leak: $phpLeak bytes\n");

    // Preliminary Execution
    trace("\nPreliminary Execution:\n");
    $drvLeak = execTest(1, $noRows1, $startStep, $endStep, $tableName, $conn1, false, true, $phpLeak);
    $totalLeak = 0;

    // Connection & Query
    $start = Max($startStep, 1);
    $end = Min($endStep, 3);
    trace("\nConnection & Direct Query Execution:\n");
    $leak = execTest($noPasses, $noRows1, $start, $end, $tableName, $conn1, false, true, $phpLeak) - $drvLeak;
    if ($leak > $totalLeak) {
        $totalLeak = $leak;
    }

    trace("\nPrepared Query Execution:\n");
    $start = Max($startStep, 2);
    $leak = execTest($noPasses, $noRows1, $start, $end, $tableName, $conn1, true, true, $phpLeak) - $drvLeak;
    if ($leak > $totalLeak) {
        $totalLeak = $leak;
    }

    // Execution
    $noRows = $noRows1;
    $start = Max($startStep, 4);
    $end = Min($endStep, 7);
    $prepared = false;
    $release =  false;
    for ($j = 0; $j < 8; $j++) {
        switch ($j) {
            case 0:
                $prepared = false;
                $release =  true;
                break;

            case 1:
                $prepared = true;
                $release =  true;
                break;

            case 2:
                $prepared = false;
                $release =  false;
                break;

            case 3:
                $prepared = true;
                $release =  false;
                break;

            case 4:
                AE\insertTestRows($conn1, $tableName, $noRows2);
                $noRows = $noRows1 + $noRows2;
                $prepared = false;
                $release =  false;
                break;

            case 5:
                $prepared = true;
                $release =  false;
                break;

            case 6:
                $prepared = false;
                $release =  true;
                break;

            case 7:
                $prepared = true;
                $release =  true;
                break;

            default:
                break;

        }
        if ($prepared) {
            trace("\nPrepared Query");
        } else {
            trace("\nDirect Query");
        }
        if ($release) {
            trace(" with statement release:\n");
        } else {
            trace(" without statement release:\n");
        }
        $leak = execTest($noPasses, $noRows, $start, $end, $tableName, $conn1, $prepared, $release, $phpLeak) - $drvLeak;
        if ($leak > $totalLeak) {
            $totalLeak = $leak;
        }
    }

    sqlsrv_close($conn1);

    $conn2 = AE\connect();
    dropTable($conn2, $tableName);
    sqlsrv_close($conn2);
    setUTF8Data(false);

    if ($totalLeak > 0) {
        die("Memory leaks detected: $totalLeak bytes\n");
    }

    endTest($testName);
}

function getConnection()
{
    $conn = AE\connect();
    return ($conn);
}

function execQuery($conn, $tableName, $prepared)
{
    $selectQuery = "SELECT * FROM [$tableName]";
    $stmt = null;

    if (AE\isColEncrypted()){
        $stmt = AE\executeQuery($conn, $selectQuery);
    } else {
        if ($prepared) {
            $stmt = sqlsrv_prepare($conn, $selectQuery);
        } else {
            $stmt = sqlsrv_query($conn, $selectQuery);
        }
        if ($stmt === false) {
            fatalError("Query execution failed: $selectQuery");
        }
        if ($prepared) {
            if (!sqlsrv_execute($stmt)) {
                fatalError("Query execution failed: $selectQuery");
            }
        }
    }
    return ($stmt);
}

function execTest($noPasses, $noRows, $startStep, $endStep, $tableName, $conn, $prepared, $release, $phpLeak)
{
    $leak = 0;

    // Execution
    for ($i = $startStep; $i <= $endStep; $i++) {
        switch ($i) {
            case 1:    // connection only
                trace("$i. Connection\t - ");
                break;

            case 2:    // query with no release
                trace("$i. Query\t - ");
                break;

            case 3:    // query with release
                trace("$i. Query Freed\t - ");
                break;

            case 4:    // fetch
                trace("$i. Simple Fetch\t - ");
                break;

            case 5:    // fetch fields
                trace("$i. Fetch Fields\t - ");
                break;

            case 6:    // fetch array
                trace("$i. Fetch Array\t - ");
                break;

            case 7:    // fetch object
                trace("$i. Fetch Object\t - ");
                break;

            default:
                break;
        }
        $memLeak = runTest($noPasses, $noRows, $tableName, $conn, $prepared, $release, $i) - $phpLeak;
        trace("Driver memory leak: $memLeak bytes\n");
        if ($memLeak > 0) {
            if ($leak <= 0) {
                $leak = $memLeak;
                echo intval($leak) . " leaking\n";
            }
        }
    }

    return ($leak);
}

function runTest($noPasses, $noRows, $tableName, $conn, $prepared, $release, $mode)
{
    $memStart =  memory_get_usage();
    for ($k = 1; $k <= $noPasses; $k++) {
        $conn2 = null;
        $stmt = null;
        $fld = null;
        $rowCount = 0;
        $numFields = 0;
        $i = 0;

        switch ($mode) {
            case 0:    // calibration
                break;

            case 1:    // no release
                $conn2 = getConnection();
                sqlsrv_close($conn2);
                break;

            case 2:    // query with no release
                $stmt = execQuery($conn, $tableName, $prepared);
                break;

            case 3:    // query with release
                $stmt = execQuery($conn, $tableName, $prepared);
                sqlsrv_free_stmt($stmt);
                break;

            case 4:    // fetch
                $stmt = execQuery($conn, $tableName, $prepared);
                while (sqlsrv_fetch($stmt)) {
                    $rowCount++;
                }
                if ($release) {
                    sqlsrv_free_stmt($stmt);
                }
                if ($rowCount != $noRows) {
                    die("$rowCount rows retrieved instead of $noRows\n");
                }
                break;

            case 5:    // fetch fields
                $stmt = execQuery($conn, $tableName, $prepared);
                $numFields = sqlsrv_num_fields($stmt);
                while (sqlsrv_fetch($stmt)) {
                    $rowCount++;
                    for ($i = 0; $i < $numFields; $i++) {
                        $fld = sqlsrv_get_field($stmt, $i);
                        $col = $i + 1;

                        if ($fld === false) {
                            fatalError("Field $i of row $rowCount is missing");
                        }
                        unset($fld);
                    }
                }
                if ($release) {
                    sqlsrv_free_stmt($stmt);
                }
                if ($rowCount != $noRows) {
                    die("$rowCount rows retrieved instead of $noRows\n");
                }
                unset($errState);
                unset($errMessage);
                break;

            case 6:    // fetch array
                $stmt = execQuery($conn, $tableName, $prepared);
                while (sqlsrv_fetch_array($stmt)) {
                    $rowCount++;
                }
                if ($release) {
                    sqlsrv_free_stmt($stmt);
                }
                if ($rowCount != $noRows) {
                    die("$rowCount rows retrieved instead of $noRows\n");
                }
                break;

            case 7:    // fetch object
                $stmt = execQuery($conn, $tableName, $prepared);
                while (sqlsrv_fetch_object($stmt)) {
                    $rowCount++;
                }
                if ($release) {
                    sqlsrv_free_stmt($stmt);
                }
                if ($rowCount != $noRows) {
                    die("$rowCount rows retrieved instead of $noRows\n");
                }
                break;

            default:
                break;

        }
        // need unset to trigger the destruction of a zval with refcount of 0
        unset($conn2);
        unset($stmt);
    }
    $memEnd =  memory_get_usage();
    trace(intval($memEnd) . " - " . intval($memStart) . "\n");
    return ($memEnd - $memStart);
}

try {
    memCheck(20, 10, 15, 1, 7);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Memory Leakage Check" completed successfully.
