--TEST--
LOB streams preserve bytes across reads, reexecution and statement destruction
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
$conn = connect();
$expected = str_repeat("a\0b", 4096);
foreach (array(false, true) as $buffered) {
    $options = $buffered ? array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
        PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED) : array();
    $stmt = $conn->prepare("SELECT CONVERT(varbinary(max), REPLICATE(CAST(0x610062 AS varbinary(max)), 4096))", $options);
    $stmt->bindColumn(1, $stream, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    for ($iteration = 0; $iteration < 2; ++$iteration) {
        $stmt->execute();
        if (!$stmt->fetch(PDO::FETCH_BOUND) || !is_resource($stream)) {
            throw new Exception('Expected a LOB stream.');
        }
        $data = fread($stream, 7);
        if ($iteration === 1) {
            $data .= stream_get_contents($stream);
        }
        if ($data !== ($iteration === 0 ? substr($expected, 0, 7) : $expected)) {
            throw new Exception('LOB bytes changed.');
        }
        fclose($stream);
        $stmt->closeCursor();
    }
    $stmt->execute();
    $stmt->fetch(PDO::FETCH_BOUND);
    unset($stmt);
    if (stream_get_contents($stream) !== $expected) {
        throw new Exception('LOB invalid after statement destruction.');
    }
    fclose($stream);
    echo $buffered ? "Buffered passed\n" : "Forward passed\n";
}
$stmt = $conn->query('SELECT 42');
if ((int) $stmt->fetchColumn() !== 42) {
    throw new Exception('Connection reuse failed.');
}
unset($stmt, $conn);
echo "Connection reused\n";
?>
--EXPECT--
Forward passed
Buffered passed
Connection reused
