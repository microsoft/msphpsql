--TEST--
False connection with LoginTimeout option
--DESCRIPTION--
Intentionally provide an invalid server and set LoginTimeout. Verify the time elapsed.
The difference in time elapsed is platform dependent. In some Linux distros, extra delay 
may be caused by the attempts to resolve non-existent hostnames. Already set leeway to 2
seconds to allow some room of such errors, but this test remains fragile, especially 
outside Windows. Thus, use an invalid IP address instead when running in any non-Windows platform.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');
$serverName = isWindows()? 'WRONG_SERVER_NAME' : '1.2.3.4';

// Based on the following reference, a login timeout of less than approximately 10 seconds
// is not reliable. The defaut is 15 seconds so we fix it at 20 seconds.
// https://docs.microsoft.com/sql/connect/odbc/windows/features-of-the-microsoft-odbc-driver-for-sql-server-on-windows
$timeout = 20;
$maxAttempts = 3;
$numAttempts = 0;

$leeway = 2.0;
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
            echo "Attempts: $numAttempts, Time difference: $diff\n";
            sleep(5);
        }
    }
} while ($missed && $numAttempts < $maxAttempts);

print "Done\n";
?>
--EXPECT--
Done
