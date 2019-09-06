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

function testTimeoutAttribute($conn, $timeout, $statementLevel = false)
{
    global $message;
    
    $error = str_replace('timeout', $timeout, $message);
    
    try {
        if ($statementLevel) {
            trace("statement option expects error: $error\n");
            $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
            $query = 'SELECT 1';
            $stmt = $conn->prepare($query, $options);
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

function runTest($conn, $timeout, $statementLevel, $asAttribute = false)
{
    $t0 = microtime(true);

    $query = 'WAITFOR DELAY \'00:00:40\'; SELECT 1';
    $error = '*Query timeout expired';

    $result = null;
    
    try {
        if (! $statementLevel) {
            $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
            trace("Connection: " . $conn->getAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT) . PHP_EOL);
            $result = $conn->exec($query);
        } else {
            if ($asAttribute) {
                $stmt = $conn->prepare($query);
                $stmt->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
            } else {
                $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 10);
                $stmt = $conn->prepare($query, $options);
            }
            trace("Statement: " . $stmt->getAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT) . PHP_EOL);
            $result = $stmt->execute();
        }
        
        echo "Expected an exception. Should have timed out!\n";
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            echo "Error unexpected for $timeout, $statementLevel, $asAttribute:\n";
            var_dump($e->getMessage());
        }
    }
    
    $t1 = microtime(true);

    $elapsed = $t1 - $t0;
    $diff = abs($elapsed - $timeout);
    $leeway = 0.5;
    $missed = ($diff > $leeway);
    trace("Time elapsed: $elapsed secs\n");

    if ($missed || $result !== null) {
        echo "Expected $timeout but $elapsed secs elapsed with result:\n";
        var_dump($result);
    }    
}

try {
    // $conn = new PDO( "sqlsrv:server=$server; Database = $databaseName", $uid, $pwd);
    // $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    $conn = connect();
    testErrors($conn);

    runTest($conn, 10, false);
    runTest($conn, 15, false);
    runTest($conn, 10, true, false);
    runTest($conn, 15, true, true);

    echo "Done\n";

    unset($conn);
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Done
