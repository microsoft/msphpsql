--TEST--
SQLSRV explicit string declarations retain their types, sizes and Unicode input behavior
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
$conn = connect(['CharacterSet' => 'UTF-8']);
$sql = "SELECT SQL_VARIANT_PROPERTY(v, 'BaseType'), SQL_VARIANT_PROPERTY(v, 'MaxLength'),
    CONVERT(nvarchar(max), v) FROM (VALUES (?)) t(v)";
foreach (['varchar' => SQLSRV_SQLTYPE_VARCHAR(4), 'nvarchar' => SQLSRV_SQLTYPE_NVARCHAR(4)] as $type => $sqlType) {
    $value = 'abcd';
    $params = [[&$value, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), $sqlType]];
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    if (!$stmt) { die(print_r(sqlsrv_errors(), true)); }
    foreach (['abcd', 'a', null, 'abcd'] as $value) {
        if (!sqlsrv_execute($stmt)) { die(print_r(sqlsrv_errors(), true)); }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        if ($row[2] !== $value || ($value !== null &&
            ($row[0] !== $type || (int)$row[1] !== ($type === 'nvarchar' ? 8 : 4)))) {
            throw new Exception('Explicit SQLSRV declaration changed');
        }
        while (sqlsrv_next_result($stmt) === true) {}
    }
    sqlsrv_free_stmt($stmt);
    echo "$type declarations: PASS\n";
}
$value = "\u{1F600}\u{65E5}";
$stmt = sqlsrv_query($conn, $sql,
    [[$value, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_NVARCHAR(3)]]);
if (!$stmt) { die(print_r(sqlsrv_errors(), true)); }
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
if ((int)$row[1] !== 6 || $row[2] !== $value) { throw new Exception('Unicode declaration changed: ' . json_encode($row)); }
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
echo "UTF-16 units: PASS\n";
?>
--EXPECT--
varchar declarations: PASS
nvarchar declarations: PASS
UTF-16 units: PASS
