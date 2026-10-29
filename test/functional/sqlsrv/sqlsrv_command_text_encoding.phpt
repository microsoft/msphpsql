--TEST--
SQLSRV command text uses strict conversion for execution and preparation
--DESCRIPTION--
Requires Linux and an installed GBK/GB2312, Big5 or CP932 locale. The current LC_CTYPE
is preferred, so separate test processes can exercise each installed locale.
Uses only table-free SELECT statements against the configured test database.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php
require('skipif.inc');
require(__DIR__ . '/../inc/command_text_encoding.inc');
if (commandTextLocale() === false) {
    die('skip requires Linux with an installed GBK/GB2312, Big5 or CP932 locale');
}
?>
--FILE--
<?php
require('MsCommon.inc');
require(__DIR__ . '/../inc/command_text_encoding.inc');
$legacy = commandTextLocale();
checkCommandText($legacy !== false, 'Legacy locale unavailable');

function expectCommandEncodingError($result)
{
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    checkCommandText(
        $result === false && $errors !== null
            && $errors[0]['SQLSTATE'] === 'IMSSP' && $errors[0]['code'] === -46,
        'Expected the query-encoding error: ' . print_r($errors, true)
    );
}

function checkCommandResult($stmt)
{
    checkCommandText($stmt !== false, 'Command failed: ' . print_r(sqlsrv_errors(), true));
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    checkCommandText($row !== null && $row !== false && $row[0] === 1, 'Command text changed');
    sqlsrv_free_stmt($stmt);
}

$utf8 = "\xE4\xB8\xAD'text";
$expected = "NCHAR(20013) + NCHAR(39) + N'text'";
foreach (['default SYSTEM', 'explicit SYSTEM', 'UTF-8'] as $mode) {
    $options = ['ConnectionPooling' => false];
    if ($mode !== 'default SYSTEM') {
        $options['CharacterSet'] = $mode === 'UTF-8' ? 'UTF-8' : SQLSRV_ENC_CHAR;
    }
    $conn = connect($options);
    $text = $mode === 'UTF-8' ? $utf8 : $legacy['text'];
    $literal = "N'" . str_replace("'", "''", $text) . "'";
    $sql = "SELECT CASE WHEN $literal = $expected THEN 1 ELSE 0 END";
    checkCommandResult(sqlsrv_query($conn, $sql));
    $stmt = sqlsrv_prepare($conn, $sql);
    checkCommandText($stmt !== false, 'Preparation failed: ' . print_r(sqlsrv_errors(), true));
    checkCommandText(sqlsrv_execute($stmt), 'Execution failed: ' . print_r(sqlsrv_errors(), true));
    checkCommandResult($stmt);

    foreach ([$legacy['lead'] . "'text", $legacy['lead']] as $invalid) {
        $sql = "SELECT N'" . str_replace("'", "''", $invalid) . "'";
        expectCommandEncodingError(sqlsrv_query($conn, $sql));
        expectCommandEncodingError(sqlsrv_prepare($conn, $sql));
    }
    $truncated = 'SELECT 1 -- ' . $legacy['lead'];
    expectCommandEncodingError(sqlsrv_query($conn, $truncated));
    expectCommandEncodingError(sqlsrv_prepare($conn, $truncated));

    $params = [[&$utf8, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_NVARCHAR(20)]];
    $sql = "SELECT CASE WHEN ? = $expected THEN 1 ELSE 0 END";
    checkCommandResult(sqlsrv_query($conn, $sql, $params));
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    checkCommandText($stmt !== false, 'Bound preparation failed: ' . print_r(sqlsrv_errors(), true));
    checkCommandText(sqlsrv_execute($stmt), 'Bound execution failed: ' . print_r(sqlsrv_errors(), true));
    checkCommandResult($stmt);

    $binary = "\x00\xBF\x27\x00";
    $params = [[&$binary, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(4)]];
    checkCommandResult(sqlsrv_query(
        $conn, 'SELECT CASE WHEN ? = 0x00BF2700 THEN 1 ELSE 0 END', $params
    ));
    sqlsrv_close($conn);
    echo "$mode: passed\n";
}
?>
--EXPECT--
default SYSTEM: passed
explicit SYSTEM: passed
UTF-8: passed
