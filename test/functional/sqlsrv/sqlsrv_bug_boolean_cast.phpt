--TEST--
Test reading boolean parameters and casts to boolean types.
--DESCRIPTION--
This test verifies that boolean parameters are read correctly and output
1 or 0 as appropriate. The expected outputs consist of a true value as a bit,
false as a bit, a true value cast to a bit, a true value as an int, true, false,
and a true directly cast to a bit.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = Connect();
if (!$conn) {
    fatalError("Could not connect");
}

$tsql = <<<SQL
DECLARE @bit_true bit = ?, @bit_false bit = ?, @bit_cast_true bit = CAST(? AS bit),
   @int_true int = ?
SELECT 'bit_true'=@bit_true, 'bit_false'=@bit_false, 'bit_cast_true'=@bit_cast_true,
   'int_true'=@int_true, 'direct_true'=?, 'direct_false'=?,
   'direct_bit_cast_true'=CAST(? AS bit)
SQL;
$stmt = sqlsrv_query($conn, $tsql, [true,false,true,true,true,false,true]);
$row = sqlsrv_fetch_object($stmt);

var_dump($row);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--
object(stdClass)#1 (7) {
  ["bit_true"]=>
  int(1)
  ["bit_false"]=>
  int(0)
  ["bit_cast_true"]=>
  int(1)
  ["int_true"]=>
  int(1)
  ["direct_true"]=>
  int(1)
  ["direct_false"]=>
  int(0)
  ["direct_bit_cast_true"]=>
  int(1)
}
