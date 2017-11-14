--TEST--
Checks that calling sqlsrv_query() after sqlsrv_begin_transaction() with an invalid query does not cause a crash.
--DESCRIPTION--
In PDO_SQLSRV, calling beginTransaction() and then query() with an invalid query can cause a crash in php.exe or
php-cgi.exe, which may manifest as a CLI crash or produce an error message saying "Faulting Module[...]odbc32.dll".
The equivalent sequence of operations in SQLSRV is not known to crash - this test is for verification. This test
tells us nothing under run-tests.php because the crash only occurs at the end of script execution, so any expected 
output already exists and the test would pass. Therefore manual verification is necessary - this test should be run
separately to verify no crash occurs.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require_once("MsSetup.inc");
    $connOptions = array("Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");

    $conn = sqlsrv_connect($server, $connOptions);

    for ($i = 0; $i < 50; $i++) {
        echo "Iteration $i\n";
        if (sqlsrv_begin_transaction($conn)) {
            $stmt = sqlsrv_query($conn, 'SELECT fakecolumn FROM faketable');
        }
        sqlsrv_commit($conn);
    }
    
    sqlsrv_close($conn);
    
    echo "Done.\n";
?>
--EXPECT--
Iteration 0
Iteration 1
Iteration 2
Iteration 3
Iteration 4
Iteration 5
Iteration 6
Iteration 7
Iteration 8
Iteration 9
Iteration 10
Iteration 11
Iteration 12
Iteration 13
Iteration 14
Iteration 15
Iteration 16
Iteration 17
Iteration 18
Iteration 19
Iteration 20
Iteration 21
Iteration 22
Iteration 23
Iteration 24
Iteration 25
Iteration 26
Iteration 27
Iteration 28
Iteration 29
Iteration 30
Iteration 31
Iteration 32
Iteration 33
Iteration 34
Iteration 35
Iteration 36
Iteration 37
Iteration 38
Iteration 39
Iteration 40
Iteration 41
Iteration 42
Iteration 43
Iteration 44
Iteration 45
Iteration 46
Iteration 47
Iteration 48
Iteration 49
Done.
