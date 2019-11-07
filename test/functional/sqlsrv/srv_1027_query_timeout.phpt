--TEST--
GitHub issue 1027 - timeout option 
--DESCRIPTION--
This test is a variant of the corresponding PDO test, and it verifies that setting the query timeout option should affect sqlsrv_query or sqlsrv_prepare correctly.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

const _DELAY = 5;

$message = 'Invalid value timeout specified for option SQLSRV_QUERY_TIMEOUT.';
$delay = _DELAY;
$query = "WAITFOR DELAY '00:00:$delay'; SELECT 1";
$expired = '*Query timeout expired';

function testTimeout($conn, $timeout, $prepare = false)
{
    global $message;

    $error = str_replace('timeout', $timeout, $message);
    $options = array('QueryTimeout' => $timeout);
    $sql = 'SELECT 1';

    if ($prepare) {
        $stmt = sqlsrv_prepare($conn, $sql, null, $options);
    } else {
        $stmt = sqlsrv_query($conn, $sql, null, $options);
    }
    
    if ($stmt !== false) {
        echo "Expect this to fail with timeout option $timeout\n";
    }
    if (sqlsrv_errors()[0]['message'] !== $error) {
        print_r(sqlsrv_errors());
    }
}

function testErrors($conn)
{
    testTimeout($conn, 1.8);
    testTimeout($conn, 'xyz');
    testTimeout($conn, -99, true);
    testTimeout($conn, 'abc', true);
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

function statementTest($conn, $timeout, $prepare)
{
    global $query, $expired;
    
    $options = array('QueryTimeout' => $timeout);
    $stmt = null;
    $result = null;

    // if timeout is 0 it means no timeout 
    $delay = ($timeout > 0) ? $timeout : _DELAY;

    $t0 = microtime(true);
    if ($prepare) {
        $stmt = sqlsrv_prepare($conn, $query, null, $options);
        $result = sqlsrv_execute($stmt);
    } else {
        $stmt = sqlsrv_query($conn, $query, null, $options);
    }
    
    $t1 = microtime(true);
    
    if ($timeout > 0) {
        if ($prepare && $result !== false) {
            echo "Prepared statement should fail with timeout $timeout\n";
        } elseif (!$prepare && $stmt !== false) {
            echo "Query should fail with timeout $timeout\n";
        } else {
            // check error messages
            $errors = sqlsrv_errors();
            if (!fnmatch($expired, $errors[0]['message'])) {
                echo "Unexpected error returned ($timeout, $prepare):\n";
                print_r(sqlsrv_errors());
            }
        }
    } 

    checkTimeElapsed("statementTest ($timeout, $prepare): ", $t0, $t1, $delay);
}

$conn = AE\connect();

testErrors($conn);

$rand = rand(1, 100);
$timeout = $rand % 3;

for ($i = 0; $i < 2; $i++) {
    statementTest($conn, $timeout, $i);
}

sqlsrv_close($conn);

echo "Done\n";

?>
--EXPECT--
Done