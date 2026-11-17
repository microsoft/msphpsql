--TEST--
PDO explicit input string declarations across bindParam, bindValue and execute
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect('', [], PDO::ERRMODE_EXCEPTION, true);
function declaration($type, $length) {
    return [PDO::SQLSRV_PARAM_DECLARED_STR_TYPE => $type,
        PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => $length];
}
function verifyRow($stmt, $type, $bytes, $value) {
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row[0] !== $type || (int)$row[1] !== $bytes || $row[2] !== $value) {
        throw new Exception('Unexpected declaration or truncated value: ' . json_encode($row));
    }
    $stmt->closeCursor();
}
$sql = "SELECT SQL_VARIANT_PROPERTY(v, 'BaseType'),
    SQL_VARIANT_PROPERTY(v, 'MaxLength'), CONVERT(nvarchar(max), v) FROM (VALUES (?)) t(v)";
$stmt = $conn->prepare($sql);
$value = 'abcd';
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0,
    declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR, 4));
$stmt->execute();
verifyRow($stmt, 'varchar', 4, $value);
$value = 'abcde';
try {
    $stmt->execute();
    throw new Exception('Overflow accepted');
} catch (PDOException $e) {
    if ($e->getCode() !== '22001') { throw $e; }
}
$value = 'a';
$stmt->execute();
verifyRow($stmt, 'varchar', 4, $value);
echo "bindParam and overflow recovery: PASS\n";

$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA,
    [1 => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR, 2)]);
// Rebinding drops the previous bindParam options, but not statement metadata.
$value = "\u{1F600}";
$stmt->bindValue(1, $value);
$stmt->execute();
verifyRow($stmt, 'nvarchar', 4, $value);
$stmt->execute(['']);
verifyRow($stmt, 'nvarchar', 4, '');
$stmt->execute([null]);
if ($stmt->fetchColumn(2) !== null) { throw new Exception('Null changed'); }
$stmt->closeCursor();
$stmt->execute([$value]);
verifyRow($stmt, 'nvarchar', 4, $value);
try {
    $stmt->execute([$value . 'x']);
    throw new Exception('UTF-16 overflow accepted');
} catch (PDOException $e) {
    if ($e->getCode() !== '22001') { throw $e; }
}
echo "bindValue, execute, null and UTF-16: PASS\n";

$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, []);
$stmt->execute(['legacy']);
verifyRow($stmt, 'nvarchar', 8000, 'legacy');
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$value = 'legacy';
$stmt->execute();
verifyRow($stmt, 'varchar', 8000, $value);
echo "reset and legacy integer options: PASS\n";

$stmt = $conn->prepare(str_replace('?', ':value', $sql), [PDO::SQLSRV_ATTR_DIRECT_QUERY => true]);
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA,
    ['value' => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR, 50)]);
$stmt->execute(['value' => 'named']);
verifyRow($stmt, 'varchar', 50, 'named');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA,
    [':value' => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR, 50)]);
$stmt->bindValue(':value', "\u{65E5}\u{672C}");
$stmt->execute();
verifyRow($stmt, 'nvarchar', 100, "\u{65E5}\u{672C}");
if ($stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA) !==
    [1 => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR, 50)]) {
    throw new Exception('Metadata getter did not normalize the name');
}
echo "named mapping and replacement: PASS\n";

$stmt = $conn->prepare("SELECT SQL_VARIANT_PROPERTY(a, 'BaseType'), SQL_VARIANT_PROPERTY(a, 'MaxLength'),
    SQL_VARIANT_PROPERTY(b, 'BaseType'), SQL_VARIANT_PROPERTY(b, 'MaxLength')
    FROM (VALUES (:narrow, :wide)) t(a, b)");
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [
    'wide' => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR, 2),
    'narrow' => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR, 7),
]);
$stmt->execute(['wide' => "\u{1F600}", 'narrow' => 'abc']);
$row = $stmt->fetch(PDO::FETCH_NUM);
$stmt->closeCursor();
if ($row[0] !== 'varchar' || (int)$row[1] !== 7 || $row[2] !== 'nvarchar' || (int)$row[3] !== 4) {
    throw new Exception('Named metadata used array order instead of placeholder order');
}
echo "multiple named declarations: PASS\n";

$placeholders = $values = $metadata = $expected = [];
for ($i = 0; $i < 838; ++$i) {
    $name = "p$i";
    $placeholders[] = ":$name";
    $values[$name] = "v$i";
    $entry = declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR, 8 + $i % 3);
    $metadata[$i % 2 ? ":$name" : $name] = $entry;
    $expected[$i + 1] = $entry;
}
$stmt = $conn->prepare('SELECT ' . implode(', ', $placeholders));
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, array_reverse($metadata, true));
if ($stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA) !== $expected) {
    throw new Exception('Large named metadata map used insertion order');
}
$stmt->execute($values);
if ($stmt->fetch(PDO::FETCH_NUM) !== array_values($values)) {
    throw new Exception('Large named parameter round trip failed');
}
$stmt->closeCursor();
echo "838 named declarations and reversed mixed aliases: PASS\n";

$conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 100);
$stmt = $conn->prepare($sql);
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA,
    [1 => declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR, 50)]);
$value = 'override';
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0,
    declaration(PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR, 10) +
    [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8]);
$stmt->execute();
verifyRow($stmt, 'varchar', 10, $value);
echo "binding declaration takes precedence: PASS\n";

foreach ([PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR,
    PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR] as $type) {
    $limit = $type === PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR ? 8000 : 4000;
    $stmt = $conn->prepare("SELECT DATALENGTH(v), CONVERT(nvarchar(max), v) FROM (VALUES (?)) t(v)");
    $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => declaration($type, $limit)]);
    $value = str_repeat('x', $limit);
    $stmt->execute([$value]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row[1] !== $value) { throw new Exception('Boundary truncated'); }
    $stmt->closeCursor();
    $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA,
        [1 => declaration($type, PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH_MAX)]);
    $value .= 'x';
    $stmt->execute([$value]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ((int)$row[0] !== ($type === PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR ? strlen($value) : strlen($value) * 2)
        || $row[1] !== $value) { throw new Exception('MAX truncated'); }
    $stmt->closeCursor();
}
echo "bounded maximum and MAX: PASS\n";
?>
--EXPECT--
bindParam and overflow recovery: PASS
bindValue, execute, null and UTF-16: PASS
reset and legacy integer options: PASS
named mapping and replacement: PASS
multiple named declarations: PASS
838 named declarations and reversed mixed aliases: PASS
binding declaration takes precedence: PASS
bounded maximum and MAX: PASS
