--TEST--
PDO defers bounded non-ASCII VARCHAR while retaining Unicode MAX and safe UTF-8 sizing
--SKIPIF--
<?php
require('skipif_mid-refactor.inc');
$conn = connect('', [], PDO::ERRMODE_EXCEPTION, true);
$codepage = $conn->query("SELECT COLLATIONPROPERTY(CAST(DATABASEPROPERTYEX(DB_NAME(), 'Collation') AS nvarchar(128)), 'CodePage')")->fetchColumn();
if ($codepage != 65001) {
    die('skip requires a database with a UTF-8 default collation (SQL Server 2019+)');
}
?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect('', [], PDO::ERRMODE_EXCEPTION, true);
$sql = "SELECT SQL_VARIANT_PROPERTY(v, 'BaseType'), SQL_VARIANT_PROPERTY(v, 'MaxLength'),
    CONVERT(nvarchar(max), v) FROM (VALUES (?)) t(v)";
$stmt = $conn->prepare($sql);
$metadata = [PDO::SQLSRV_PARAM_DECLARED_STR_TYPE => PDO::SQLSRV_PARAM_DECLARED_STR_TYPE_VARCHAR,
    PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH => 7];
$value = "\u{65E5}\u{1F600}";
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0, $metadata);
foreach (["\u{65E5}\u{1F600}", "\u{65E5}\u{1F600}a"] as $value) {
    try {
        $stmt->execute();
        throw new Exception('Bounded non-ASCII VARCHAR accepted without collation validation');
    } catch (PDOException $e) {
        if ($e->getCode() !== 'IMSSP') { throw $e; }
    }
}
echo "bounded non-ASCII VARCHAR deferred: PASS\n";

$stmt = $conn->prepare('SELECT CONVERT(nvarchar(max), ?)');
$metadata[PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH] = PDO::SQLSRV_PARAM_DECLARED_STR_LENGTH_MAX;
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => $metadata]);
$stmt->execute([$value]);
if ($stmt->fetchColumn() !== $value) { throw new Exception('Explicit MAX changed Unicode transport'); }
$stmt->closeCursor();
echo "explicit Unicode VARCHAR(MAX): PASS\n";

$conn->setAttribute(PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH, 4);
$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt = $conn->prepare($sql);
foreach (["\u{65E5}\u{1F600}", "\u{00E9}"] as $value) {
    $stmt->execute([$value]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $stmt->closeCursor();
    if ($row[0] !== 'varchar' || (int)$row[1] !== 7 || $row[2] !== $value) {
        throw new Exception('Unicode high-water sizing changed');
    }
}
echo "UTF-8 byte high-water: PASS\n";

$stmt = $conn->prepare('SELECT CONVERT(nvarchar(max), ?)');
foreach ([str_repeat("\u{1F600}", 2000), str_repeat("\u{1F600}", 2001), 'x'] as $value) {
    $stmt->execute([$value]);
    if ($stmt->fetchColumn() !== $value) { throw new Exception('Unicode MAX promotion truncated'); }
    $stmt->closeCursor();
}
echo "Unicode MAX round trip: PASS\n";

$stmt = $conn->prepare('SELECT ?');
$stmt->setAttribute(PDO::SQLSRV_ATTR_PARAM_METADATA, [1 => $metadata]);
try {
    $stmt->execute(["\xC3\x28"]);
    throw new Exception('Malformed UTF-8 accepted');
} catch (PDOException $e) {
    if ($e->getCode() !== 'IMSSP') { throw $e; }
}
echo "malformed UTF-8 rejected: PASS\n";
?>
--EXPECT--
bounded non-ASCII VARCHAR deferred: PASS
explicit Unicode VARCHAR(MAX): PASS
UTF-8 byte high-water: PASS
Unicode MAX round trip: PASS
malformed UTF-8 rejected: PASS
