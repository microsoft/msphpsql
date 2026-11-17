--TEST--
PDO rejects invalid string metadata and unsupported binding paths without changing configuration
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect('', [], PDO::ERRMODE_EXCEPTION, true);
function rejected($operation) {
    try {
        $operation();
    } catch (PDOException $e) {
        if ($e->getCode() !== 'IMSSP') { throw $e; }
        return;
    }
    throw new Exception('Invalid option accepted');
}
$valid = [PDO::SQLSRV_PARAM_DECLARED_STR_TYPE => PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_NVARCHAR,
    PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => 4];
$stmt = $conn->prepare('SELECT :value');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, ['value' => $valid]);
$metadata = $stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA);
rejected(fn() => $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, null));
if ($stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA) !== $metadata) {
    throw new Exception('Null setter changed metadata');
}
echo "null metadata rejection preserves declaration: PASS\n";
foreach ([null, 3, [], [PDO::SQLSRV_PARAM_DECLARED_STR_TYPE => 12],
    array_replace($valid, [PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => 0]),
    array_replace($valid, [PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => -2]),
    array_replace($valid, [PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => 4001]),
    array_replace($valid, [PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => '4']),
    array_replace($valid, [PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => true]),
    array_replace($valid, [PDO::SQLSRV_PARAM_DECLARED_STR_TYPE => PDO::PARAM_STR]),
    $valid + [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_BINARY],
    $valid + ['unknown' => 5]] as $invalid) {
    rejected(fn() => $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => $invalid]));
}
foreach ([[0 => $valid], [-1 => $valid], [2101 => $valid], ['missing' => $valid],
    ['value' => $valid, ':value' => $valid], [1 => $valid, 'value' => $valid]] as $invalid) {
    rejected(fn() => $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, $invalid));
}
$stmt->execute(['value' => 'safe']);
if ($stmt->fetchColumn() !== 'safe') { throw new Exception('Invalid setter changed metadata'); }
$stmt->closeCursor();
echo "strict validation and atomic replacement: PASS\n";

$stmt = $conn->prepare('SELECT :value, :value, :unique');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, ['unique' => $valid]);
$metadata = $stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA);
foreach (['value', ':value'] as $name) {
    rejected(fn() => $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, ['unique' => $valid, $name => $valid]));
    if ($stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA) !== $metadata) {
        throw new Exception('Ambiguous name changed metadata');
    }
}
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, []);
if ($stmt->getAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA) !== []) {
    throw new Exception('Repeated placeholders prevented metadata reset');
}
echo "ambiguous names reject atomically and allow reset: PASS\n";

foreach ([-1, 8001, '4', true, 1.5, null] as $invalid) {
    rejected(fn() => $conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, $invalid));
}
if ($conn->getAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH) !== 0) {
    throw new Exception('Invalid setter changed default');
}
$stmt = $conn->prepare('SELECT ?');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [2 => $valid]);
rejected(fn() => $stmt->execute(['x']));
echo "invalid defaults and unbound metadata: PASS\n";

$stmt = $conn->prepare('SELECT ?');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => [
    PDO::SQLSRV_PARAM_DECLARED_STR_TYPE => PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR,
    PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => 20,
]]);
rejected(fn() => $stmt->execute(["\u{00E9}"]));
echo "non-ASCII bounded VARCHAR is explicitly unsupported: PASS\n";

$stmt = $conn->prepare('SELECT ?', [PDO::ATTR_EMULATE_PREPARES => true]);
rejected(fn() => $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => $valid]));
$value = 'text';
rejected(function () use ($stmt, &$value, $valid) {
    $stmt->bindParam(1, $value, PDO::PARAM_STR, 0, $valid);
});
$conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 4);
rejected(fn() => $conn->prepare('SELECT ?', [PDO::ATTR_EMULATE_PREPARES => true]));
$conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 0);

$stmt = $conn->prepare('SELECT ?');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => $valid]);
$stmt->bindValue(1, 12, PDO::PARAM_INT);
rejected(fn() => $stmt->execute());
$stmt->bindParam(1, $value, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4);
rejected(fn() => $stmt->execute());
$stream = fopen('php://memory', 'r+');
$stmt->bindParam(1, $stream, PDO::PARAM_LOB);
rejected(fn() => $stmt->execute());
fclose($stream);
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
rejected(fn() => $stmt->execute());
echo "unsupported paths: PASS\n";

$encrypted = connect('ColumnEncryption=Enabled;', [], PDO::ERRMODE_EXCEPTION, false);
$stmt = $encrypted->prepare('SELECT 1');
rejected(fn() => $stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => $valid]));
$encrypted->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 4);
rejected(fn() => $encrypted->prepare('SELECT ?'));
echo "Always Encrypted rejected: PASS\n";
?>
--EXPECT--
null metadata rejection preserves declaration: PASS
strict validation and atomic replacement: PASS
ambiguous names reject atomically and allow reset: PASS
invalid defaults and unbound metadata: PASS
non-ASCII bounded VARCHAR is explicitly unsupported: PASS
unsupported paths: PASS
Always Encrypted rejected: PASS
