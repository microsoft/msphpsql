--TEST--
GitHub issue 1027 - PDO::SQLSRV_ATTR_QUERY_TIMEOUT had no effect on PDO::exec()
--DESCRIPTION--
This test verifies that setting PDO::SQLSRV_ATTR_QUERY_TIMEOUT correctly should affect PDO::exec() as in the case for PDO::prepare() (as statement attribute or option).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

const _DELAY = 5;

$message = '*Invalid value timeout specified for option PDO::SQLSRV_ATTR_QUERY_TIMEOUT.';
$delay = _DELAY;
$query = "WAITFOR DELAY '00:00:$delay'; SELECT 1";
$error = '*Query timeout expired';

function testTimeoutAttribute($conn, $timeout, $statementLevel = false)
{
    global $message;

    $error = str_replace('timeout', $timeout, $message);

    try {
        if ($statementLevel) {
            trace("statement option expects error: $error\n");
            $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
            $sql = 'SELECT 1';
            $stmt = $conn->prepare($sql, $options);
        } else {
            trace("connection attribute expects error: $error\n");
            $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
        }
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            echo "Unexpected error returned setting invalid $timeout for SQLSRV_ATTR_QUERY_TIMEOUT\n";
            var_dump($e->getMessage());
        }
    }
}

function testErrors($conn)
{
    testTimeoutAttribute($conn, 1.8);
    testTimeoutAttribute($conn, 'xyz');
    testTimeoutAttribute($conn, -99, true);
    testTimeoutAttribute($conn, 'abc', true);
}

function checkTimeElapsed($message, $t0, $t1, $expectedDelay)
{
    $elapsed = $t1 - $t0;
    $diff = abs($elapsed - $expectedDelay);
    $leeway = 1.0;
    $missed = ($diff > $leeway);
    trace("$message $elapsed secs elapsed\n");

    if ($missed) {
        echo $message;
        echo "Expected $expectedDelay but $elapsed secs elapsed\n";
    }
}

function connectionTest($timeout, $asAttribute)
{
    global $query, $error;
    $keyword = ''; 

    if ($asAttribute) {
        $conn = connect($keyword);
        $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
    } else {
        $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
        $conn = connect($keyword, $options);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // if timeout is 0 it means no timeout 
    $delay = ($timeout > 0) ? $timeout : _DELAY;

    $result = null;
    $t0 = microtime(true);

    try {
        $result = $conn->exec($query);
        if ($timeout > 0) {
            echo "connectionTest $timeout, $asAttribute: ";
            echo "this should have timed out!\n";
        } 
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            echo "Connection test error expected $timeout, $asAttribute:\n";
            var_dump($e->getMessage());
        }
    }

    $t1 = microtime(true);
    checkTimeElapsed("connectionTest ($timeout, $asAttribute): ", $t0, $t1, $delay);

    return $conn;
}

function queryTest($conn, $timeout)
{
    global $query, $error;

    // if timeout is 0 it means no timeout 
    $delay = ($timeout > 0) ? $timeout : _DELAY;
    
    $t0 = microtime(true);
    try {
        $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
        $stmt = $conn->query($query);
        
        if ($timeout > 0) {
            echo "Query test $timeout: should have timed out!\n";
        }
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            echo "Query test error expected $timeout:\n";
            var_dump($e->getMessage());
        }
    }

    $t1 = microtime(true);

    checkTimeElapsed("Query test ($timeout): ", $t0, $t1, $delay);
    
    unset($stmt);
}

function statementTest($conn, $timeout, $asAttribute)
{
    global $query, $error;

    // if timeout is 0 it means no timeout 
    $delay = ($timeout > 0) ? $timeout : _DELAY;

    $result = null;
    $t0 = microtime(true);

    try {
        if ($asAttribute) {
            $stmt = $conn->prepare($query);
            $stmt->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
        } else {
            $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
            $stmt = $conn->prepare($query, $options);
        }

        $result = $stmt->execute();

        if ($timeout > 0) {
            echo "statementTest $timeout: should have timed out!\n";
        }
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            echo "Statement test error expected $timeout, $asAttribute:\n";
            var_dump($e->getMessage());
        }
    }

    $t1 = microtime(true);

    checkTimeElapsed("statementTest ($timeout, $asAttribute): ", $t0, $t1, $delay);
    
    unset($stmt);
}

try {
    $rand = rand(1, 100);
    $timeout = $rand % 3;
    $asAttribute = $rand % 2;
    
    $conn = connectionTest($timeout, $asAttribute);
    testErrors($conn);
    unset($conn);
    
    $conn = connectionTest(0, !$asAttribute);
    queryTest($conn, $timeout);
    
    for ($i = 0; $i < 2; $i++) {
        statementTest($conn, $timeout, $i);
    }
    unset($conn);

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Done
