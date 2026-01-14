--TEST--
GitHub issue 1100 - PDO::SQLSRV_ATTR_QUERY_TIMEOUT had no effect when reconnecting
--DESCRIPTION--
This test verifies that setting PDO::SQLSRV_ATTR_QUERY_TIMEOUT should work when reconnecting after disconnecting
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

function checkTimeElapsed($t0, $t1, $expectedDelay)
{
    $elapsed = $t1 - $t0;
    $diff = abs($elapsed - $expectedDelay);
    $leeway = 1.0;
    $missed = ($diff > $leeway);
    trace("$elapsed secs elapsed\n");

    if ($missed) {
        echo "Expected $expectedDelay but $elapsed secs elapsed\n";
    }
}

function testTimeout($conn, $timeout)
{
    $delay = 5;
    $query = "WAITFOR DELAY '00:00:$delay'; SELECT 1";
    $error = '*Query timeout expired';

    echo "Starting testTimeout...\n";
    $t0 = microtime(true);
    try {
        $conn->exec($query);
        $elapsed = microtime(true) - $t0;
        echo "Should have failed after $timeout secs but $elapsed secs have elapsed" . PHP_EOL;
    } catch (PDOException $e) {
        $t1 = microtime(true);
        echo "Query timed out as expected\n";
        
        $message = '*Query timeout expired';
        if (!fnmatch($message, $e->getMessage())) {
            var_dump($e->getMessage());
        }
        checkTimeElapsed($t0, $t1, $timeout);
    }
    echo "Finished testTimeout\n";
}

try {
    $keywords = 'MultipleActiveResultSets=false;';
    $timeout = 1;
    
    echo "Test 1: Setting timeout in connection options\n";
    $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
    $conn = connect($keywords, $options);
    echo "Connected with options\n";

    testTimeout($conn, $timeout);
    echo "Disconnecting...\n";
    unset($conn);
    echo "Disconnected\n";

    echo "Test 2: Setting timeout with setAttribute\n";
    $conn = connect($keywords);
    echo "Connected without options\n";
    $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);
    echo "Set timeout attribute\n";

    testTimeout($conn, $timeout);
    echo "Disconnecting...\n";
    unset($conn);
    echo "Disconnected\n";

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Test 1: Setting timeout in connection options
Connected with options
Starting testTimeout...
Query timed out as expected
Finished testTimeout
Disconnecting...
Disconnected
Test 2: Setting timeout with setAttribute
Connected without options
Set timeout attribute
Starting testTimeout...
Query timed out as expected
Finished testTimeout
Disconnecting...
Disconnected
Done
