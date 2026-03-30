--TEST--
Test SQLSRV_ENC_UTF8_VARCHAR with stream params and output params
--DESCRIPTION--
Covers additional code paths for SQLSRV_ENCODING_UTF8_VARCHAR:
- Stream/resource parameters (process_resource_param and core_stream.cpp)
- Output parameters from stored procedures (process_output_string)
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsErrors', 0);
require_once('MsCommon.inc');

$conn = connect(array('CharacterSet' => SQLSRV_ENC_UTF8_VARCHAR));
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$tbname = '#utf8vc_extra_' . rand(0, 1000);
$procName = '#sp_utf8vc_out_' . rand(0, 1000);

// Create table
$stmt = sqlsrv_query($conn, "CREATE TABLE $tbname (
    id int IDENTITY(1,1) NOT NULL,
    name varchar(255) COLLATE Latin1_General_100_CI_AS_SC_UTF8 NULL,
    content varchar(max) COLLATE Latin1_General_100_CI_AS_SC_UTF8 NULL
)");
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

$testVal = 'Straße Köln Grüß Gott';
$streamContent = 'UTF-8 stream data with special chars: Grüße 日本語';

// === Test 1: Stream/resource parameter ===
echo "=== Test 1: Stream parameter ===\n";

// Create a temp file with UTF-8 content
$tmpFile = tempnam(sys_get_temp_dir(), 'utf8');
file_put_contents($tmpFile, $streamContent);

$fp = fopen($tmpFile, 'r');
$stmt = sqlsrv_query($conn,
    "INSERT INTO $tbname (name, content) VALUES (?, ?)",
    [$testVal, &$fp],
    array('SendStreamParamsAtExec' => 1)
);
if ($stmt === false) {
    echo "Stream insert failed:\n";
    print_r(sqlsrv_errors());
} else {
    sqlsrv_free_stmt($stmt);

    // Read back and verify
    $stmt = sqlsrv_query($conn, "SELECT name, content FROM $tbname WHERE id = 1");
    if (sqlsrv_fetch($stmt)) {
        $name = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR));
        $content = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UTF8_VARCHAR));
        echo "name: " . (($name === $testVal) ? 'PASS' : 'FAIL') . "\n";
        echo "content: " . (($content === $streamContent) ? 'PASS' : 'FAIL') . "\n";
    }
    sqlsrv_free_stmt($stmt);
}
fclose($fp);
unlink($tmpFile);

// === Test 2: Output parameter ===
echo "=== Test 2: Output parameter ===\n";

$stmt = sqlsrv_query($conn, "CREATE PROCEDURE $procName @id INT, @out_name VARCHAR(255) OUTPUT
AS
BEGIN
    SELECT @out_name = name FROM $tbname WHERE id = @id
END");
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

$outName = str_repeat(' ', 255);
$stmt = sqlsrv_prepare($conn, "EXEC $procName ?, ?",
    array(
        array(1, SQLSRV_PARAM_IN),
        array(&$outName, SQLSRV_PARAM_INOUT, null, SQLSRV_SQLTYPE_VARCHAR(255))
    )
);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
sqlsrv_execute($stmt);
// Consume all result sets to finalize output params
while (sqlsrv_next_result($stmt) !== false);
sqlsrv_free_stmt($stmt);

$outName = rtrim($outName);
echo "output: " . (($outName === $testVal) ? 'PASS' : 'FAIL') . "\n";

// === Test 3: Fetch as stream ===
echo "=== Test 3: Fetch as stream ===\n";
$stmt = sqlsrv_query($conn, "SELECT content FROM $tbname WHERE id = 1");
if (sqlsrv_fetch($stmt)) {
    $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_UTF8_VARCHAR));
    if ($stream !== false) {
        $data = stream_get_contents($stream);
        fclose($stream);
        echo "stream: " . (($data === $streamContent) ? 'PASS' : 'FAIL') . "\n";
    } else {
        echo "stream: FAIL (get_field returned false)\n";
    }
}
sqlsrv_free_stmt($stmt);

// Cleanup
sqlsrv_query($conn, "DROP PROCEDURE $procName");
sqlsrv_query($conn, "DROP TABLE $tbname");
sqlsrv_close($conn);
echo "Done\n";
?>
--EXPECT--
=== Test 1: Stream parameter ===
name: PASS
content: PASS
=== Test 2: Output parameter ===
output: PASS
=== Test 3: Fetch as stream ===
stream: PASS
Done
