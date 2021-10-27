--TEST--
GitHub issue 1310 - bind null field as varchar(max) if not binary
--DESCRIPTION--
The test shows null fields are no longer bound as char(1) if not binary such that it solves both issues 1310 and 1102.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

// Issue 1310
$query = "SELECT CAST(ISNULL(?, -1) AS INT) AS K";
$k = null;
$params = array($k);

$stmt = sqlsrv_prepare($conn, $query, $params);
if (!$stmt) {
    fatalError("Failed to prepare statement (1).");
}
if (!sqlsrv_execute($stmt)) {
    fatalError("Failed to execute query (1).");
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    var_dump($row);
}

sqlsrv_free_stmt($stmt);

// Issue 1102
$query = "DECLARE @d DATETIME = ISNULL(?, GETDATE()); SELECT @d AS D;";
$k = null;
$params = array($k, null, null, null);

$options = array('ReturnDatesAsStrings'=> true);
$stmt = sqlsrv_query($conn, $query, $params, $options);
if (!$stmt) {
    fatalError("Failed to query statement (2).");
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    var_dump($row);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done\n";

?>
--EXPECTREGEX--
array\(1\) {
  \["K"\]=>
  int\(-1\)
}
array\(1\) {
  \["D"\]=>
  string\(23\) "20[0-9]{2}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:00.000"
}
Done
