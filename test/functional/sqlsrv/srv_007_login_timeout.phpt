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

$conn = sqlsrv_connect($serverName , array("LoginTimeout" => 8));

$t1 = microtime(true);

echo "Connection attempt time: " . ($t1 - $t0) . " [sec]\n";

print "Done";
?>

--EXPECTREGEX--
Connection attempt time: [7-9]\.[0-9]+ \[sec\]
Done
