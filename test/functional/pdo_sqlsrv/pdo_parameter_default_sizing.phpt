--TEST--
PDO opt-in default string sizing grows without shrinking and preserves legacy defaults
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect('', [PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH => 4], PDO::ERRMODE_EXCEPTION, true);
if ($conn->getAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH) !== 4) {
    throw new Exception('Constructor sizing option was not retained');
}
$sql = "SELECT SQL_VARIANT_PROPERTY(v, 'BaseType'), SQL_VARIANT_PROPERTY(v, 'MaxLength'),
    CONVERT(nvarchar(max), v) FROM (VALUES (?)) t(v)";
foreach ([PDO::SQLSRV_ENCODING_UTF8 => 'nvarchar', PDO::SQLSRV_ENCODING_UTF8_VARCHAR => 'varchar'] as $encoding => $type) {
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $encoding);
    $stmt = $conn->prepare($sql);
    foreach (['abcd', 'abcdef', 'a', '', null, 'abcd'] as $i => $value) {
        $stmt->execute([$value]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $stmt->closeCursor();
        $size = ($i === 0 ? 4 : 6) * ($type === 'nvarchar' ? 2 : 1);
        if ($row[2] !== $value || ($value !== null && ($row[0] !== $type || (int)$row[1] !== $size))) {
            throw new Exception('Default sizing or high-water mark incorrect');
        }
    }
    echo "$type growth and high-water: PASS\n";
}
$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
$stmt = $conn->prepare($sql);
$stmt->execute(["\u{1F600}\u{1F600}\u{1F600}"]);
$row = $stmt->fetch(PDO::FETCH_NUM);
$stmt->closeCursor();
if ((int)$row[1] !== 12 || $row[2] !== "\u{1F600}\u{1F600}\u{1F600}") {
    throw new Exception('Sizing must count UTF-16 units, not UTF-8 bytes');
}
$conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 0);
$stmt->execute(['x']);
$row = $stmt->fetch(PDO::FETCH_NUM);
$stmt->closeCursor();
if ((int)$row[1] !== 12) { throw new Exception('Existing statement settings changed'); }
$new = $conn->prepare($sql);
$new->execute(['x']);
if ((int)$new->fetchColumn(1) !== 8000) { throw new Exception('Disabled sizing changed legacy declaration'); }
$new->closeCursor();
echo "Unicode units, snapshot and opt-out: PASS\n";

$conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 4);
$stmt = $conn->prepare("SELECT SQL_VARIANT_PROPERTY(a, 'MaxLength'),
    SQL_VARIANT_PROPERTY(b, 'MaxLength') FROM (VALUES (?, ?)) t(a, b)");
$stmt->execute(['abcdef', 'x']);
$row = $stmt->fetch(PDO::FETCH_NUM);
$stmt->closeCursor();
if ((int)$row[0] !== 12 || (int)$row[1] !== 8) { throw new Exception('Sizing leaked across positions'); }
$stmt->execute(['a', 'xyz']);
$row = $stmt->fetch(PDO::FETCH_NUM);
$stmt->closeCursor();
if ((int)$row[0] !== 12 || (int)$row[1] !== 8) { throw new Exception('Independent high-water marks changed'); }
echo "per-position growth: PASS\n";

foreach ([PDO::SQLSRV_ENCODING_UTF8 => 4000, PDO::SQLSRV_ENCODING_UTF8_VARCHAR => 8000] as $encoding => $limit) {
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $encoding);
    $stmt = $conn->prepare("SELECT CONVERT(nvarchar(max), ?) AS value");
    foreach ([str_repeat('a', $limit), str_repeat('b', $limit + 1), 'c'] as $value) {
        $stmt->execute([$value]);
        if ($stmt->fetchColumn() !== $value) { throw new Exception('Promotion to MAX truncated data'); }
        $stmt->closeCursor();
    }
}
echo "MAX growth round trip: PASS\n";

$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
$stmt = $conn->prepare($sql);
$stmt->bindValue(1, 42, PDO::PARAM_INT);
$stmt->execute();
if ($stmt->fetchColumn() !== 'int') { throw new Exception('Sizing changed integer type'); }
$stmt->closeCursor();
$stmt = $conn->prepare('SELECT CONVERT(varchar(max), ?)');
$stream = fopen('php://memory', 'r+');
fwrite($stream, 'stream');
rewind($stream);
$stmt->bindParam(1, $stream, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
if ($stmt->fetchColumn() !== 'stream') { throw new Exception('Sizing changed stream'); }
$stmt->closeCursor();
fclose($stream);
$stmt = $conn->prepare('SET NOCOUNT ON; SET ? = ?');
$output = 'old';
$stmt->bindParam(1, $output, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
$stmt->bindValue(2, 'longer output');
$stmt->execute();
$stmt->closeCursor();
if ($output !== 'longer output') { throw new Exception('Sizing changed output buffer semantics'); }
echo "non-string contracts: PASS\n";
?>
--EXPECT--
nvarchar growth and high-water: PASS
varchar growth and high-water: PASS
Unicode units, snapshot and opt-out: PASS
per-position growth: PASS
MAX growth round trip: PASS
non-string contracts: PASS
