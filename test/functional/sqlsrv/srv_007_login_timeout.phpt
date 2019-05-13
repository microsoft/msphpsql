--TEST--
False connection with LoginTimeout option
--DESCRIPTION--
Intentionally provide an invalid server name and set LoginTimeout. Verify the time elapsed.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

$serverName = "WRONG_SERVER_NAME";

$t0 = microtime(true);

// Based on the following reference, a login timeout of less than approximately 10 seconds 
// is not reliable. The defaut is 15 seconds so we fix it at 20 seconds.
// https://docs.microsoft.com/sql/connect/odbc/windows/features-of-the-microsoft-odbc-driver-for-sql-server-on-windows

$timeout = 20;  
$conn = sqlsrv_connect($serverName , array("LoginTimeout" => $timeout));

$t1 = microtime(true);

$elapsed = $t1 - $t0;
$diff = abs($elapsed - $timeout);

if ($elapsed < $timeout || $diff > 1.0) {
    echo "Connection failed at $elapsed secs. Leeway is 1.0 sec but the difference is $diff\n";
}

print "Done";
?>
--EXPECT--
Done
