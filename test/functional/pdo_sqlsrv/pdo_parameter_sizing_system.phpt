--TEST--
PDO default VARCHAR sizing allows expansion from a system code page to SQL UTF-8
--SKIPIF--
<?php
require('skipif_mid-refactor.inc');
if (!function_exists('sapi_windows_cp_get') || sapi_windows_cp_get('ansi') !== 1252) {
    die('skip requires Windows code page 1252');
}
$conn = connect('', [], PDO::ERRMODE_EXCEPTION, true);
$codepage = $conn->query("SELECT COLLATIONPROPERTY(CAST(DATABASEPROPERTYEX(DB_NAME(), 'Collation') AS nvarchar(128)), 'CodePage')")->fetchColumn();
if ($codepage != 65001) {
    die('skip requires a UTF-8 database collation');
}
?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect('', [PDO::SQLSRV_ATTR_DEFAULT_STR_LENGTH => 1], PDO::ERRMODE_EXCEPTION, true);
$stmt = $conn->prepare("SELECT DATALENGTH(v), CONVERT(nvarchar(max), v) FROM (VALUES (?)) t(v)");
$value = "\xE9";
$stmt->bindParam(1, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_NUM);
if ((int)$row[0] !== 2 || $row[1] !== "\u{00E9}") {
    throw new Exception('System-to-SQL code-page expansion truncated input: ' . json_encode($row));
}
$stmt->closeCursor();
echo "system code-page expansion: PASS\n";
?>
--EXPECT--
system code-page expansion: PASS
