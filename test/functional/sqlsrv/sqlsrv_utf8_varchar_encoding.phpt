--TEST--
SQLSRV UTF8_VARCHAR parameter types, encoding overrides and Unicode reads
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function parameterType($conn, $params)
{
    $stmt = sqlsrv_query($conn, "SELECT SQL_VARIANT_PROPERTY(?, 'BaseType')", $params);
    if ($stmt === false || sqlsrv_fetch($stmt) !== true) {
        die(print_r(sqlsrv_errors(), true));
    }
    $type = sqlsrv_get_field($stmt, 0);
    sqlsrv_free_stmt($stmt);
    return $type;
}

$conn = connect(['CharacterSet' => SQLSRV_ENC_UTF8_VARCHAR]);
echo "connection: ", parameterType($conn, ['ascii']), "\n";
echo "legacy override: ", parameterType($conn, [
    ['ascii', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')]
]), "\n";
echo "explicit SQL type: ", parameterType($conn, [
    ['ascii', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR), SQLSRV_SQLTYPE_NVARCHAR(20)]
]), "\n";

$value = "\u{65E5}\u{672C}\u{8A9E}\u{1F600}";
$stmt = sqlsrv_query($conn, "SELECT ?", [
    [$value, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR), SQLSRV_SQLTYPE_NVARCHAR(20)]
]);
if ($stmt === false || sqlsrv_fetch($stmt) !== true) {
    die(print_r(sqlsrv_errors(), true));
}
echo "explicit Unicode: ", (sqlsrv_get_field($stmt, 0) === $value ? 'PASS' : 'FAIL'), "\n";
sqlsrv_free_stmt($stmt);

$stream = fopen('php://temp', 'w+b');
fwrite($stream, 'ascii');
rewind($stream);
echo "stream type: ", parameterType($conn, [
    [$stream, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_UTF8_VARCHAR), SQLSRV_SQLTYPE_VARCHAR(100)]
]), "\n";
fclose($stream);

foreach ([SQLSRV_CURSOR_FORWARD, SQLSRV_CURSOR_CLIENT_BUFFERED] as $cursor) {
    $stmt = sqlsrv_query($conn, "SELECT N'$value', CAST('ascii' AS varchar(20)),
        CAST(12.5 AS decimal(4,1))", [], ['Scrollable' => $cursor]);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    echo "fetch: ", ($row === [$value, 'ascii', '12.5'] ? 'PASS' : 'FAIL'), "\n";
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);

$conn = connect(['CharacterSet' => 'UTF-8']);
echo "legacy: ", parameterType($conn, ['ascii']), "\n";
echo "parameter: ", parameterType($conn, [
    ['ascii', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR)]
]), "\n";
sqlsrv_close($conn);
?>
--EXPECT--
connection: varchar
legacy override: nvarchar
explicit SQL type: nvarchar
explicit Unicode: PASS
stream type: varchar
fetch: PASS
fetch: PASS
legacy: nvarchar
parameter: varchar
