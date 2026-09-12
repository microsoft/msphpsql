--TEST--
Column metadata preserves MAX, binary, text and decimal lengths and scales
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
$conn = connect();
$columns = array(
    array('varchar(max)', 0, 0),
    array('nvarchar(max)', 0, 0),
    array('varbinary(max)', 0, 0),
    array('binary(16)', 16, 0),
    array('varbinary(32)', 32, 0),
    array('varchar(37)', 37, 0),
    array('nvarchar(19)', 19, 0),
    array('text', 2147483647, 0),
    array('ntext', 1073741823, 0),
    array('image', 2147483647, 0),
    array('decimal(38,4)', 38, 4)
);
$definitions = array();
foreach ($columns as $index => $column) {
    $definitions[] = "c$index " . $column[0] . ' NULL';
}
$conn->exec('CREATE TABLE #pdoMetadataLengths (' . implode(', ', $definitions) . ')');
try {
    $conn->exec('INSERT INTO #pdoMetadataLengths DEFAULT VALUES');
    foreach ($columns as $index => list($type, $length, $scale)) {
        $stmt = $conn->prepare("SELECT c$index AS value FROM #pdoMetadataLengths");
        for ($iteration = 0; $iteration < 2; ++$iteration) {
            $stmt->execute();
            $meta = $stmt->getColumnMeta(0);
            if ($meta['name'] !== 'value' || $meta['len'] !== $length || $meta['precision'] !== $scale) {
                var_dump($type, $meta);
                throw new Exception('Unexpected column metadata.');
            }
            if ($stmt->fetch(PDO::FETCH_NUM) !== array(null)) {
                throw new Exception('Unexpected column value.');
            }
            $stmt->closeCursor();
        }
        unset($stmt);
        echo "$type: passed\n";
    }
} finally {
    unset($stmt);
    $conn->exec('DROP TABLE #pdoMetadataLengths');
}
unset($conn);
?>
--EXPECT--
varchar(max): passed
nvarchar(max): passed
varbinary(max): passed
binary(16): passed
varbinary(32): passed
varchar(37): passed
nvarchar(19): passed
text: passed
ntext: passed
image: passed
decimal(38,4): passed
