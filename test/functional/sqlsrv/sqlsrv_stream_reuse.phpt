--TEST--
Binary and text streams support chunked reads, early close, statement reuse and connection reuse
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
$conn = connect();
if ($conn === false) {
    fatalError('Connection failed.');
}
foreach (array('varbinary(max)', 'varchar(max)', 'nvarchar(max)') as $type) {
    $stmt = sqlsrv_prepare($conn, "SELECT CONVERT($type, REPLICATE(CAST('abc' AS varchar(max)), 4096))");
    if ($stmt === false) {
        fatalError('Prepare failed.');
    }
    for ($iteration = 0; $iteration < 2; ++$iteration) {
        if (!sqlsrv_execute($stmt) || !sqlsrv_fetch($stmt)) {
            fatalError('Execute or fetch failed.');
        }
        $encoding = $type === 'varbinary(max)' ? SQLSRV_ENC_BINARY : 'UTF-8';
        $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM($encoding));
        if (!is_resource($stream)) {
            fatalError('Expected a stream.');
        }
        $data = fread($stream, 7);
        if ($iteration === 1) {
            while (!feof($stream)) {
                $chunk = fread($stream, 257);
                if ($chunk === false) {
                    fatalError('Stream read failed.');
                }
                $data .= $chunk;
            }
        }
        $expected = $iteration === 0 ? 'abcabca' : str_repeat('abc', 4096);
        if ($data !== $expected || !fclose($stream)) {
            fatalError('Stream contents or close failed.');
        }
    }
    sqlsrv_free_stmt($stmt);
    echo "$type: passed\n";
}
$stmt = sqlsrv_query($conn, 'SELECT 42');
if ($stmt === false || !sqlsrv_fetch($stmt) || sqlsrv_get_field($stmt, 0) !== 42) {
    fatalError('Connection reuse failed.');
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
echo "Connection reused\n";
?>
--EXPECT--
varbinary(max): passed
varchar(max): passed
nvarchar(max): passed
Connection reused
