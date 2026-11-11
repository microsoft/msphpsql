--TEST--
PDO UTF8_VARCHAR parameter types, encoding overrides and Unicode reads
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect();
$typeSql = "SELECT SQL_VARIANT_PROPERTY(?, 'BaseType')";

$stmt = $conn->prepare($typeSql);
$stmt->execute(['ascii']);
echo "legacy: ", $stmt->fetchColumn(), "\n";
$stmt->closeCursor();

$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt = $conn->prepare($typeSql);
$stmt->execute(['ascii']);
echo "connection: ", $stmt->fetchColumn(), "\n";
$stmt->closeCursor();

$stmt = $conn->prepare($typeSql);
$value = 'ascii';
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
echo "legacy override: ", $stmt->fetchColumn(), "\n";
$stmt->closeCursor();

$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
$stmt = $conn->prepare($typeSql, [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8_VARCHAR]);
$stmt->execute(['ascii']);
echo "statement: ", $stmt->fetchColumn(), "\n";
$stmt->closeCursor();

$stmt = $conn->prepare($typeSql);
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt->execute();
echo "parameter: ", $stmt->fetchColumn(), "\n";
$stmt->closeCursor();

// Extended string flags must keep their existing native-prepare behavior.
foreach ([PDO::PARAM_STR_CHAR, PDO::PARAM_STR_NATL] as $flag) {
    $stmt = $conn->prepare($typeSql);
    $stmt->bindValue(1, 'ascii', PDO::PARAM_STR | $flag);
    $stmt->execute();
    echo "legacy flag: ", $stmt->fetchColumn(), "\n";
    $stmt->closeCursor();
}
$conn->setAttribute(PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_CHAR);
$stmt = $conn->prepare($typeSql);
$stmt->execute(['ascii']);
echo "legacy default: ", $stmt->fetchColumn(), "\n";
$stmt->closeCursor();

// Server-generated Unicode independently exercises command text and fetch conversion.
$value = "\u{65E5}\u{672C}\u{8A9E}\u{1F600}";
$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
foreach ([PDO::CURSOR_FWDONLY, PDO::CURSOR_SCROLL] as $cursor) {
    $options = [PDO::ATTR_CURSOR => $cursor];
    if ($cursor == PDO::CURSOR_SCROLL) {
        $options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;
    }
    $stmt = $conn->prepare("SELECT N'$value' AS unicode_value,
        CAST('ascii' AS varchar(20)) AS narrow_value, CAST(12.5 AS decimal(4,1)) AS number_value", $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    echo "fetch: ", ($row === [$value, 'ascii', '12.5'] ? 'PASS' : 'FAIL'), "\n";
    $stmt->closeCursor();
}
$conn = null;
?>
--EXPECT--
legacy: nvarchar
connection: varchar
legacy override: nvarchar
statement: varchar
parameter: varchar
legacy flag: nvarchar
legacy flag: nvarchar
legacy default: nvarchar
fetch: PASS
fetch: PASS
