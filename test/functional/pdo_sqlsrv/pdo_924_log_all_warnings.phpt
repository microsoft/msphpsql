--TEST--
GitHub issue 924 - verifies the warnings or error messages are logged to a log file
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function toConnect()
{
    require("MsSetup.inc");
    
    $dsn = getDSN($server, $databaseName, $driver);
    $conn = new PDO($dsn, $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
}

function printCursor($cursorArray)
{
    if ($cursorArray[PDO::ATTR_CURSOR] == PDO::CURSOR_FWDONLY) {
        $cursor = 'FORWARD ONLY cursor';
    } else {
        switch ($cursorArray[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE]) {
            case PDO::SQLSRV_CURSOR_DYNAMIC:
                $cursor = 'server side DYNAMIC cursor';
                break;
            case PDO::SQLSRV_CURSOR_STATIC:
                $cursor = 'server side STATIC cursor';
                break;
            case PDO::SQLSRV_CURSOR_KEYSET:
                $cursor = 'server side KEYSET cursor';
                break;
            case PDO::SQLSRV_CURSOR_BUFFERED:
                $cursor = 'client side BUFFERED cursor';
                break;
            default:
                $cursor = 'error';
                break;
        }
    }
    
    echo "#####Testing $cursor#####\n";
    return $cursor;
}

function checkResults($data, $results, $resultSet, $expectedRows)
{
    $failed = false;
    for ($j = 0; $j < $expectedRows; $j++) {
        if ($results[$j][0] != $data[$resultSet][$j]) {
            $failed = true;
            echo "Fetched results unexpected at row $j:\n";
            print_r($results[$j]);
            break;
        }
    }
    
    return $failed;
}

try {
    ini_set('log_errors', '1');

    $logFilename = 'php_924_cursors.log';
    $logFilepath = dirname(__FILE__).'/'.$logFilename;
    
    if (file_exists($logFilepath)) {
        unlink($logFilepath);
    }

    ini_set('error_log', $logFilepath);
    ini_set('pdo_sqlsrv.log_severity', '3');    // warnings and errors only 
  
    // All supported cursor types
    $cursors = array(array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY),
                     array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_DYNAMIC),
                     array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_STATIC),
                     array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_KEYSET),
                     array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED),
                    );


    // Data for testing, all integer types
    $data = array(array(86, -217483648, 0, -432987563, 7, 217483647),
                  array(0, 31, 127, 255, 1, 10),
                  array(4534, -212, 32767, 0, 7, -32768),
                  array(-1, 546098342985600, 9223372000000000000, 5115115115115, 7, -7),
                  array(0, 1, 0, 0, 1, 1),
                 );

    $tableName = 'pdo_924_batchquery_test';
    
    // Column names
    $colName = array('c1_int', 'c2_tinyint', 'c3_smallint', 'c4_bigint', 'c5_bit');
    $columns = array(new ColumnMeta('int', $colName[0]),
                 new ColumnMeta('tinyint', $colName[1]),
                 new ColumnMeta('smallint',$colName[2]),
                 new ColumnMeta('bigint', $colName[3]),
                 new ColumnMeta('bit', $colName[4]));

    $conn = toConnect();
    createTable($conn, $tableName, $columns);

    $expectedRows = sizeof($data[0]);

    // Expected result sets = number of columns, since the batch fetches each column sequentially
    $expectedResultSets = count($colName);

    // Insert each row. Need an associative array to use insertRow()
    for ($i = 0; $i < $expectedRows; ++$i) {
        $inputs = array();
        for ($j = 0; $j < $expectedResultSets; ++$j) {
            $inputs[$colName[$j]] = $data[$j][$i];
        }

        $stmt = insertRow($conn, $tableName, $inputs);
        unset($stmt);
    }

    $query = "SELECT c1_int FROM $tableName;
              SELECT c2_tinyint FROM $tableName;
              SELECT c3_smallint FROM $tableName;
              SELECT c4_bigint FROM $tableName;
              SELECT c5_bit FROM $tableName;";

    for ($i = 0; $i < sizeof($cursors); ++$i) {
        $cursorType = $cursors[$i];
        // $cursor = printCursor($i);
        $cursor = printCursor($cursorType);

        $stmt = $conn->prepare($query, $cursorType);
        $stmt->execute();
        
        $numResultSets = 0;
        do {
            $res = $stmt->fetchAll(PDO::FETCH_NUM);
            $failed = checkResults($data, $res, $numResultSets, $expectedRows);
            ++$numResultSets;
        } while (!$failed && $stmt->nextRowset());
        
        if ($numResultSets != $expectedResultSets) {
            echo ("Unexpected number of result sets, expected $expectedResultedSets, got $numResultSets\n");
            break;
        }

        if (file_exists($logFilepath)) {
            echo file_get_contents($logFilepath);
            unlink($logFilepath);
        }
        
        unset($stmt);
        echo "#####Finished testing with $cursor#####\n";
    }
    
    // Now reset logging by disabling it
    ini_set('pdo_sqlsrv.log_severity', '0');

    dropTable($conn, $tableName);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

echo "Done.\n";

?>
--EXPECTF--
#####Testing FORWARD ONLY cursor#####
[%s UTC] pdo_sqlsrv_db_handle_factory: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_db_handle_factory: error code = 5701
[%s UTC] pdo_sqlsrv_db_handle_factory: message = %s[SQL Server]Changed database context to '%s'.
[%s UTC] pdo_sqlsrv_db_handle_factory: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_db_handle_factory: error code = 5703
[%s UTC] pdo_sqlsrv_db_handle_factory: message = %s[SQL Server]Changed language setting to us_english.
#####Finished testing with FORWARD ONLY cursor#####
#####Testing server side DYNAMIC cursor#####
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 0
[%s UTC] pdo_sqlsrv_stmt_execute: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 16954
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Executing SQL directly; no cursor.
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
#####Finished testing with server side DYNAMIC cursor#####
#####Testing server side STATIC cursor#####
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 0
[%s UTC] pdo_sqlsrv_stmt_execute: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 16954
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Executing SQL directly; no cursor.
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
#####Finished testing with server side STATIC cursor#####
#####Testing server side KEYSET cursor#####
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 0
[%s UTC] pdo_sqlsrv_stmt_execute: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 16954
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Executing SQL directly; no cursor.
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
[%s UTC] pdo_sqlsrv_stmt_next_rowset: SQLSTATE = 01S02
[%s UTC] pdo_sqlsrv_stmt_next_rowset: error code = 0
[%s UTC] pdo_sqlsrv_stmt_next_rowset: message = %sCursor type changed
#####Finished testing with server side KEYSET cursor#####
#####Testing client side BUFFERED cursor#####
#####Finished testing with client side BUFFERED cursor#####
Done.