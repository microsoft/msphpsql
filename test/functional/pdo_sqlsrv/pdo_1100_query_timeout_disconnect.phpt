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

    $t0 = microtime(true);
    try {
        $conn->exec($query);
        $elapsed = microtime(true) - $t0;
        echo "Should have failed after $timeout secs but $elapsed secs have elapsed" . PHP_EOL;
    } catch (PDOException $e) {
        $t1 = microtime(true);
        
        $message = '*Query timeout expired';
        if (!fnmatch($message, $e->getMessage())) {
            var_dump($e->getMessage());
        }
        checkTimeElapsed($t0, $t1, $timeout);
    }
}

try {
    $keywords = 'MultipleActiveResultSets=false;';
    $timeout = 1;
    
    $options = array(PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $timeout);
    $conn = connect($keywords, $options);

    testTimeout($conn, $timeout);
    unset($conn);

    $conn = connect($keywords);
    $conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, $timeout);

    testTimeout($conn, $timeout);
    unset($conn);

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Done
