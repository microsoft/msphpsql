--TEST--
False connection with LoginTimeout option
--DESCRIPTION--
Intentionally provide an invalid server name and set LoginTimeout. Verify the time elapsed.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

$serverName = "WRONG_SERVER_NAME";

// Based on the following reference, a login timeout of less than approximately 10 seconds
// is not reliable. The defaut is 15 seconds so we fix it at 20 seconds.
// https://docs.microsoft.com/sql/connect/odbc/windows/features-of-the-microsoft-odbc-driver-for-sql-server-on-windows

$timeout = 20;
$maxAttempts = 3;
$numAttempts = 0;
$leeway = 1.0;
$missed = false;

do {
    $t0 = microtime(true);

    $conn = sqlsrv_connect($serverName , array("LoginTimeout" => $timeout));
    $numAttempts++;

    $t1 = microtime(true);

    // Sometimes time elapsed might be less than expected timeout, such as 19.99* 
    // something, but 1.0 second leeway should be reasonable
    $elapsed = $t1 - $t0;
    $diff = abs($elapsed - $timeout);

    $missed = ($diff > $leeway);
    if ($missed) {
        if ($numAttempts == $maxAttempts) {
            echo "Connection failed at $elapsed secs. Leeway is $leeway sec but the difference is $diff\n";
        } else {
            // The test will fail but this helps us decide if this test should be redesigned
            echo "$numAttempts\t";
            sleep(5);
        }
    }
} while ($missed && $numAttempts < $maxAttempts);

print "Done\n";
?>
--EXPECT--
Done
