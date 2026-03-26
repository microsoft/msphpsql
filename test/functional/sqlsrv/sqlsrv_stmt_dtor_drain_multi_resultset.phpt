--TEST--
Verify stmt dtor drains unconsumed result sets from multiple SELECT batches (sqlsrv)
--DESCRIPTION--
Exercises the drain code in sqlsrv_stmt_dtor by executing a batch
that returns multiple result sets, then freeing the statement without
consuming them.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = connect();

// Execute a batch returning multiple result sets via sqlsrv_query
$stmt = sqlsrv_query($conn, "SELECT 1 AS a; SELECT 2 AS b; SELECT 3 AS c");

// Only fetch from the first result set
sqlsrv_fetch($stmt);
echo "First result: " . sqlsrv_get_field($stmt, 0) . "\n";

// Free statement with 2 unconsumed result sets — exercises the drain loop
sqlsrv_free_stmt($stmt);

// Verify connection still works
$stmt = sqlsrv_query($conn, "SELECT 42 AS answer");
sqlsrv_fetch($stmt);
echo "After drain: " . sqlsrv_get_field($stmt, 0) . "\n";
sqlsrv_free_stmt($stmt);

sqlsrv_close($conn);
echo "Done.\n";
?>
--EXPECT--
First result: 1
After drain: 42
Done.
