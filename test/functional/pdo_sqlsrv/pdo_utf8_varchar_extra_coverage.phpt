--TEST--
PDO UTF8_VARCHAR Unicode input, streams, output parameters and emulated prepares
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
$value = "\u{65E5}\u{672C}\u{8A9E}\u{1F600}";
$table = '#utf8_varchar';
$conn->exec("CREATE TABLE $table (id int, value varchar(max) COLLATE Latin1_General_100_CI_AS_SC_UTF8)");

foreach (['connection', 'statement', 'parameter'] as $scope) {
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $scope === 'connection'
        ? PDO::SQLSRV_ENCODING_UTF8_VARCHAR : PDO::SQLSRV_ENCODING_UTF8);
    $options = $scope === 'statement'
        ? [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8_VARCHAR] : [];
    $stmt = $conn->prepare("INSERT INTO $table VALUES (1, ?)", $options);
    if ($scope === 'parameter') {
        $stmt->bindParam(1, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
        $stmt->execute();
    } else {
        $stmt->execute([$value]);
    }
    $stmt->closeCursor();
    // Read through the existing UTF-8 path, not through the encoding under test.
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    $stmt = $conn->query("SELECT value FROM $table");
    echo "$scope: ", ($stmt->fetchColumn() === $value ? 'PASS' : 'FAIL'), "\n";
    $stmt->closeCursor();
    $conn->exec("DELETE FROM $table");
}

$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
// Exercise the 8000-byte VARCHAR boundary, MAX promotion and packet boundaries.
foreach ([7999, 8000, 8001, 20000] as $length) {
    $long = str_repeat('a', $length - strlen($value)) . $value;
    $stmt = $conn->prepare("INSERT INTO $table VALUES (2, ?)");
    $stmt->execute([$long]);
    $stmt->closeCursor();
    $stmt = $conn->prepare("SELECT value FROM $table",
        [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8]);
    $stmt->execute();
    echo "length $length: ", ($stmt->fetchColumn() === $long ? 'PASS' : 'FAIL'), "\n";
    $stmt->closeCursor();
    $conn->exec("DELETE FROM $table");
}

$long = str_repeat('a', 8189) . str_repeat($value, 1000);
$stream = fopen('php://temp', 'w+b');
fwrite($stream, $long);
rewind($stream);
$stmt = $conn->prepare("INSERT INTO $table VALUES (3, ?)");
$stmt->bindParam(1, $stream, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt->execute();
$stmt->closeCursor();
fclose($stream);
$stmt = $conn->prepare("SELECT value FROM $table",
    [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8]);
$stmt->execute();
echo "stream storage: ", ($stmt->fetchColumn() === $long ? 'PASS' : 'FAIL'), "\n";
$stmt->closeCursor();
$stmt = $conn->prepare("SELECT value, CAST(value AS nvarchar(max)) FROM $table", [
    PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
    PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED
]);
$stmt->execute();
$stmt->bindColumn(1, $bound, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt->bindColumn(2, $wide, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt->fetch(PDO::FETCH_BOUND);
echo "stream and bound columns: ", ($bound === $long && $wide === $long ? 'PASS' : 'FAIL'), "\n";
$stmt->closeCursor();

// A batch assignment exercises output finalization without requiring a persistent procedure.
$out = 'before';
$stmt = $conn->prepare("SET NOCOUNT ON; SET ? = ?;");
$stmt->bindParam(1, $out, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 255, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt->bindParam(2, $value, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
$stmt->closeCursor();
echo "output: ", ($out === $value ? 'PASS' : 'FAIL'), "\n";

$stream = fopen('php://temp', 'w+b');
fwrite($stream, "invalid\xff");
rewind($stream);
$stmt = $conn->prepare("INSERT INTO $table VALUES (4, ?)");
$stmt->bindParam(1, $stream, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
try {
    $stmt->execute();
    echo "invalid stream: FAIL\n";
} catch (PDOException $e) {
    echo "invalid stream: ", ($e->getCode() === 'IMSSP' ? 'PASS' : 'FAIL'), "\n";
}
$stmt->closeCursor();
fclose($stream);

$nul = $value . "\0" . $value;
$stream = fopen('php://temp', 'w+b');
fwrite($stream, $nul);
rewind($stream);
$stmt = $conn->prepare("INSERT INTO $table VALUES (5, ?)");
$stmt->bindParam(1, $stream, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$stmt->execute();
$stmt->closeCursor();
fclose($stream);
$stmt = $conn->query("SELECT CONVERT(varbinary(max), value) FROM $table WHERE id = 5");
echo "NUL stream storage: ", ($stmt->fetchColumn() === $nul ? 'PASS' : 'FAIL'), "\n";
$stmt->closeCursor();

$type = uniqid('pdo_utf8_varchar_');
$conn->exec("CREATE TYPE $type AS TABLE (narrow_value varchar(max) COLLATE Latin1_General_100_CI_AS_SC_UTF8,
    wide_value nvarchar(max), binary_value varbinary(max), number_value int)");
try {
    foreach (['connection', 'parameter'] as $scope) {
        $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $scope === 'connection'
            ? PDO::SQLSRV_ENCODING_UTF8_VARCHAR : PDO::SQLSRV_ENCODING_UTF8);
        $tvp = [$type => [[$long, $long, "binary\0data", 42]]];
        $stmt = $conn->prepare("SELECT * FROM ?");
        if ($scope === 'parameter') {
            $stmt->bindParam(1, $tvp, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
        } else {
            $stmt->bindParam(1, $tvp, PDO::PARAM_LOB);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_NUM);
        echo "TVP $scope: ", ($row === [$long, $long, "binary\0data", '42'] ? 'PASS' : 'FAIL'), "\n";
        $stmt->closeCursor();
        $stmt = null;
    }
} finally {
    $conn->exec("DROP TYPE $type");
}

$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
$stmt = $conn->prepare("SELECT SQL_VARIANT_PROPERTY(?, 'BaseType'), ?");
$stmt->execute(['ascii', $value]);
$row = $stmt->fetch(PDO::FETCH_NUM);
echo "emulate: ", ($row === ['varchar', $value] ? 'PASS' : 'FAIL'), "\n";
$stmt->closeCursor();
$conn->exec("DROP TABLE $table");
$conn = null;
?>
--EXPECT--
connection: PASS
statement: PASS
parameter: PASS
length 7999: PASS
length 8000: PASS
length 8001: PASS
length 20000: PASS
stream storage: PASS
stream and bound columns: PASS
output: PASS
invalid stream: PASS
NUL stream storage: PASS
TVP connection: PASS
TVP parameter: PASS
emulate: PASS
