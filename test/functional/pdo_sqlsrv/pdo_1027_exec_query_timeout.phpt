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

$message = '*Invalid value timeout specified for option PDO::SQLSRV_ATTR_QUERY_TIMEOUT.';
$delay = 40;
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
    testTimeoutAttribute($conn, -1);
    testTimeoutAttribute($conn, 'xyz');
    testTimeoutAttribute($conn, -99, true);
    testTimeoutAttribute($conn, 'abc', true);
}

function checkTimeElapsed($t0, $t1, $delay)
{
    $elapsed = $t1 - $t0;
    $diff = abs($elapsed - $delay);
    $leeway = 1.0;
    $missed = ($diff > $leeway);
    trace("Time elapsed: $elapsed secs\n");

    if ($missed) {
        echo "Expected $delay but $elapsed secs elapsed\n";
    }
}

function connectionTest($timeout, $asAttribute = false)
{
    global $delay, $query, $error;

    if ($asAttribute) {
        $conn = connect();
        $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
    } else {
        $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
        $conn = connect('', $options);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // if timeout is 0 it means no timeout 
    if ($timeout > 0) {
        $delay = $timeout;
    }

    $result = null;
    $t0 = microtime(true);

    try {
        $result = $conn->exec($query);
        if ($timeout > 0) {
            echo "connectionTest $timeout: should have timed out!\n";
        }
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            echo "Connection test error expected $timeout, $asAttribute:\n";
            var_dump($e->getMessage());
        }
    }

    $t1 = microtime(true);
    checkTimeElapsed($t0, $t1, $delay);

    return $conn;
}

function statementTest($conn, $timeout, $asAttribute = false)
{
    global $delay, $query, $error;

    // if timeout is 0 it means no timeout 
    if ($timeout > 0) {
        $delay = $timeout;
    }

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

    checkTimeElapsed($t0, $t1, $delay);
}

try {
    $rand = rand(0, 15);
    $asAttribute = $rand % 2;
    
    $conn = connectionTest($rand, $asAttribute);
    testErrors($conn);
    statementTest($conn, $rand, $asAttribute);
    unset($conn);

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Done
