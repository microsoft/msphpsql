--TEST--
SQLSRV UTF8_VARCHAR Unicode input, streams and output parameters
--SKIPIF--
<?php
require('skipif_versions_old.inc');
$conn = connect();
$stmt = sqlsrv_query($conn, "SELECT COLLATIONPROPERTY(CAST(DATABASEPROPERTYEX(DB_NAME(), 'Collation') AS nvarchar(128)), 'CodePage')");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_fetch($stmt);
if (sqlsrv_get_field($stmt, 0) != 65001) {
    die('skip requires a database with a UTF-8 default collation (SQL Server 2019+)');
}
?>
--FILE--
<?php
require_once('MsCommon.inc');

function checkedQuery($conn, $sql, $params = [], $options = [])
{
    $stmt = sqlsrv_query($conn, $sql, $params, $options);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

$conn = connect(['CharacterSet' => SQLSRV_ENC_UTF8_VARCHAR]);
$value = "\u{65E5}\u{672C}\u{8A9E}\u{1F600}";
sqlsrv_free_stmt(checkedQuery($conn, "CREATE TABLE #utf8_varchar (value varchar(max) COLLATE Latin1_General_100_CI_AS_SC_UTF8)"));
foreach ([7999, 8000, 8001, 20000] as $length) {
    $long = str_repeat('a', $length - strlen($value)) . $value;
    sqlsrv_free_stmt(checkedQuery($conn, "INSERT INTO #utf8_varchar VALUES (?)", [$long]));
    $stmt = checkedQuery($conn, "SELECT value FROM #utf8_varchar");
    sqlsrv_fetch($stmt);
    $actual = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING('UTF-8'));
    echo "length $length: ", ($actual === $long ? 'PASS' : 'FAIL'), "\n";
    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt(checkedQuery($conn, "DELETE FROM #utf8_varchar"));
}

$long = str_repeat('a', 8189) . str_repeat($value, 1000);
$stream = fopen('php://temp', 'w+b');
fwrite($stream, $long);
rewind($stream);
sqlsrv_free_stmt(checkedQuery($conn, "INSERT INTO #utf8_varchar VALUES (?)",
    [[$stream, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_UTF8_VARCHAR)]],
    ['SendStreamParamsAtExec' => true]));
fclose($stream);

foreach ([SQLSRV_CURSOR_FORWARD, SQLSRV_CURSOR_CLIENT_BUFFERED] as $cursor) {
    $stmt = checkedQuery($conn, "SELECT value, CAST(value AS nvarchar(max)) FROM #utf8_varchar",
        [], ['Scrollable' => $cursor]);
    sqlsrv_fetch($stmt);
    $actual = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING('UTF-8'));
    $stream = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_UTF8_VARCHAR));
    if ($stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $fromStream = stream_get_contents($stream);
    fclose($stream);
    echo "stream: ", ($actual === $long && $fromStream === $long ? 'PASS' : 'FAIL'), "\n";
    sqlsrv_free_stmt($stmt);
}

$out = 'before';
$stmt = checkedQuery($conn, "SET NOCOUNT ON; SET ? = ?;", [
    [&$out, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR), SQLSRV_SQLTYPE_VARCHAR(255)],
    [$value, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')]
]);
while (($next = sqlsrv_next_result($stmt)) === true) {
}
if ($next === false) {
    die(print_r(sqlsrv_errors(), true));
}
echo "output: ", ($out === $value ? 'PASS' : 'FAIL'), "\n";
sqlsrv_free_stmt($stmt);

$out = '';
$stmt = checkedQuery($conn, "SET NOCOUNT ON; SET ? = ?;", [
    [&$out, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR), SQLSRV_SQLTYPE_VARCHAR(255)],
    [$value, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8')]
]);
while (($next = sqlsrv_next_result($stmt)) === true) {
}
if ($next === false) {
    die(print_r(sqlsrv_errors(), true));
}
echo "output only: ", ($out === $value ? 'PASS' : 'FAIL'), "\n";
sqlsrv_free_stmt($stmt);

$stream = fopen('php://temp', 'w+b');
fwrite($stream, "invalid\xff");
rewind($stream);
$stmt = sqlsrv_query($conn, "INSERT INTO #utf8_varchar VALUES (?)",
    [[$stream, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_UTF8_VARCHAR)]]);
$errors = sqlsrv_errors();
echo "invalid stream: ", ($stmt === false && $errors[0]['SQLSTATE'] === 'IMSSP' ? 'PASS' : 'FAIL'), "\n";
if ($stmt !== false) {
    sqlsrv_free_stmt($stmt);
}
fclose($stream);
$nul = $value . "\0" . $value;
$stream = fopen('php://temp', 'w+b');
fwrite($stream, $nul);
rewind($stream);
sqlsrv_free_stmt(checkedQuery($conn, "DELETE FROM #utf8_varchar"));
sqlsrv_free_stmt(checkedQuery($conn, "INSERT INTO #utf8_varchar VALUES (?)",
    [[$stream, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_UTF8_VARCHAR)]]));
fclose($stream);
$stmt = checkedQuery($conn, "SELECT CONVERT(varbinary(max), value) FROM #utf8_varchar");
sqlsrv_fetch($stmt);
echo "NUL stream storage: ", (sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)) === $nul ? 'PASS' : 'FAIL'), "\n";
sqlsrv_free_stmt($stmt);

$type = uniqid('srv_utf8_varchar_');
sqlsrv_free_stmt(checkedQuery($conn, "CREATE TYPE $type AS TABLE (
    narrow_value varchar(max) COLLATE Latin1_General_100_CI_AS_SC_UTF8,
    wide_value nvarchar(max), binary_value varbinary(max), number_value int)"));
try {
    $tvp = [$type => [[$long, $long, "binary\0data", 42]]];
    $stmt = checkedQuery($conn, "SELECT * FROM ?", [[$tvp]]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    echo "TVP connection: ", ($row === [$long, $long, "binary\0data", 42] ? 'PASS' : 'FAIL'), "\n";
    sqlsrv_free_stmt($stmt);

    $legacy = connect(['CharacterSet' => 'UTF-8']);
    try {
        $stmt = checkedQuery($legacy, "SELECT * FROM ?", [
            [$tvp, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR)]
        ]);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        echo "TVP parameter: ", ($row === [$long, $long, "binary\0data", 42] ? 'PASS' : 'FAIL'), "\n";
        sqlsrv_free_stmt($stmt);
    } finally {
        sqlsrv_close($legacy);
    }
} finally {
    sqlsrv_free_stmt(checkedQuery($conn, "DROP TYPE $type"));
}
sqlsrv_free_stmt(checkedQuery($conn, "DROP TABLE #utf8_varchar"));
sqlsrv_close($conn);
?>
--EXPECT--
length 7999: PASS
length 8000: PASS
length 8001: PASS
length 20000: PASS
stream: PASS
stream: PASS
output: PASS
output only: PASS
invalid stream: PASS
NUL stream storage: PASS
TVP connection: PASS
TVP parameter: PASS
