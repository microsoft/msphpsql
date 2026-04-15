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
if ($stmt === false) {
    echo "Query failed:\n";
    print_r(sqlsrv_errors());
    exit(1);
}
$row = sqlsrv_fetch_object($stmt);
if ($row === false || $row === null) {
    echo "Fetch failed (" . gettype($row) . "):\n";
    print_r(sqlsrv_errors());
    exit(1);
}

// Debug: show raw types and values
foreach (get_object_vars($row) as $key => $value) {
    echo "DEBUG: $key = " . var_export($value, true) . " (" . gettype($value) . ")\n";
}

// Validate each field's value (cast to int for consistent comparison
// across PHP versions and platforms where bool vs int return types vary)
$expected = [
    'bit_true' => 1,
    'bit_false' => 0,
    'bit_cast_true' => 1,
    'int_true' => 1,
    'direct_true' => 1,
    'direct_false' => 0,
    'direct_bit_cast_true' => 1,
];

$passed = true;
foreach ($expected as $key => $expectedVal) {
    $actual = (int)$row->$key;
    if ($actual !== $expectedVal) {
        echo "FAIL: $key expected $expectedVal got $actual\n";
        $passed = false;
    }
}
if ($passed) {
    echo "Test passed.\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECTF--
DEBUG: bit_true = %s (%s)
DEBUG: bit_false = %s (%s)
DEBUG: bit_cast_true = %s (%s)
DEBUG: int_true = %s (%s)
DEBUG: direct_true = %s (%s)
DEBUG: direct_false = %s (%s)
DEBUG: direct_bit_cast_true = %s (%s)
Test passed.
