--TEST--
GitHub issue 1079 - fetching sql_variant types using client buffers
--DESCRIPTION--
This test verifies that fetching sql_variant types using client buffers is supported.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

$funcName = 'SRV1079';
dropFunc($conn, $funcName);

$tsql = "CREATE FUNCTION [$funcName](@OP1 sql_variant, @OP2 sql_variant) RETURNS sql_variant AS
BEGIN DECLARE @Result sql_variant SET @Result = CASE WHEN @OP1 >= @OP2 THEN @OP1 ELSE @OP2 END RETURN @Result END";

$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError('Could not create function\n');
}

$tsql = "SELECT [dbo].[$funcName](5, 6) AS RESULT";
$stmt = sqlsrv_prepare($conn, $tsql, array(), array("Scrollable" => SQLSRV_CURSOR_CLIENT_BUFFERED, "ClientBufferMaxKBSize" => 1000));

if (!$stmt) {
    fatalError('Could not prepare query\n');
}

$result = sqlsrv_execute($stmt);
if (!$result) {
    fatalError('Executing the query failed\n');
}

foreach (sqlsrv_field_metadata($stmt) as $fieldMetadata) {
    var_dump($fieldMetadata);
}

dropFunc($conn, $funcName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(6) {
  ["Name"]=>
  string(6) "RESULT"
  ["Type"]=>
  int(-150)
  ["Size"]=>
  int(10)
  ["Precision"]=>
  NULL
  ["Scale"]=>
  NULL
  ["Nullable"]=>
  int(1)
}