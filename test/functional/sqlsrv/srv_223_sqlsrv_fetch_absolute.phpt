--TEST--
sqlsrv_fetch() with SQLSRV_SCROLL_ABSOLUTE using out of range offset
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

// connect
$conn = AE\connect();

// Prepare the statement
$sql = "select * from cd_info";
$stmt = sqlsrv_prepare($conn, $sql, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
if ($stmt === false) {
    printErrors();
}
sqlsrv_execute($stmt);

// Get row count
$row_count = sqlsrv_num_rows($stmt);
if ($row_count == 0) {
    printErrors("There should be at least one row!\n");
}

sqlsrv_execute($stmt);
$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
$field = sqlsrv_get_field($stmt, 0);
if (! $field) {
    printErrors();
}

$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_LAST);
$field = sqlsrv_get_field($stmt, 0);
if (! $field) {
    printErrors();
}

// this should return false
$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, $row_count);
if ($row) {
    printErrors("This should return false!");
}
$field = sqlsrv_get_field($stmt, 0);
if ($field !== false) {
    printErrors("This should have resulted in error!");
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done";
?>

--EXPECT--
Done
