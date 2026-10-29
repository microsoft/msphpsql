--TEST--
PDO command text uses strict conversion for execution and preparation
--DESCRIPTION--
Requires Linux and an installed GBK/GB2312, Big5 or CP932 locale. The current LC_CTYPE
is preferred, so separate test processes can exercise each installed locale.
Uses only table-free SELECT statements against the configured test database.
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

function expectCommandEncodingError($operation)
{
    try {
        $operation();
    } catch (PDOException $e) {
        checkCommandText(
            $e->errorInfo[0] === 'IMSSP' && (int) $e->errorInfo[1] === -47,
            'Expected the query-encoding error, got ' . $e->getMessage()
        );
        return;
    }
    throw new RuntimeException('Malformed command text was accepted');
}

function checkCommandResult($stmt)
{
    checkCommandText((int) $stmt->fetchColumn() === 1, 'Command text changed');
    $stmt->closeCursor();
}

$utf8 = "\xE4\xB8\xAD'text";
$expected = "NCHAR(20013) + NCHAR(39) + N'text'";
$conn = connect([], false);
$conn->setAttribute(PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_NATL);

foreach (['default UTF-8', 'explicit UTF-8', 'SYSTEM'] as $mode) {
    if ($mode !== 'default UTF-8') {
        $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $mode === 'SYSTEM'
            ? PDO::SQLSRV_ENCODING_SYSTEM : PDO::SQLSRV_ENCODING_UTF8);
    }
    $text = $mode === 'SYSTEM' ? $legacy['text'] : $utf8;
    $sql = 'SELECT CASE WHEN ' . $conn->quote($text) . " = $expected THEN 1 ELSE 0 END";
    checkCommandResult($conn->query($sql));
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    checkCommandResult($stmt);

    foreach ([$legacy['lead'] . "'text", $legacy['lead']] as $invalid) {
        $sql = 'SELECT ' . $conn->quote($invalid);
        expectCommandEncodingError(function () use ($conn, $sql) {
            $conn->exec($sql);
        });
        expectCommandEncodingError(function () use ($conn, $sql) {
            $conn->prepare($sql);
        });
    }
    $truncated = 'SELECT 1 -- ' . $legacy['lead'];
    expectCommandEncodingError(function () use ($conn, $truncated) {
        $conn->exec($truncated);
    });
    expectCommandEncodingError(function () use ($conn, $truncated) {
        $conn->prepare($truncated);
    });

    foreach ([false, true] as $named) {
        $placeholder = $named ? ':value' : '?';
        $key = $named ? ':value' : 1;
        foreach ([PDO::SQLSRV_ENCODING_DEFAULT, $mode === 'SYSTEM'
            ? PDO::SQLSRV_ENCODING_SYSTEM : PDO::SQLSRV_ENCODING_UTF8] as $encoding) {
            foreach (['array', 'bindValue', 'bindParam'] as $binding) {
                // The emulation parser reserves a placeholder followed by '=' for output assignment.
                $stmt = $conn->prepare(
                    "SELECT CASE WHEN $expected = $placeholder THEN 1 ELSE 0 END",
                    [PDO::ATTR_EMULATE_PREPARES => true, PDO::SQLSRV_ATTR_ENCODING => $encoding]
                );
                $value = $legacy['lead'] . "'text";
                if ($binding === 'bindValue') {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                } elseif ($binding === 'bindParam') {
                    $stmt->bindParam($key, $value, PDO::PARAM_STR);
                }
                expectCommandEncodingError(function () use ($stmt, $binding, $named, $value) {
                    if ($binding === 'array') {
                        $stmt->execute($named ? ['value' => $value] : [$value]);
                    } else {
                        $stmt->execute();
                    }
                });

                $value = $text;
                if ($binding === 'bindValue') {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
                for ($i = 0; $i < 2; ++$i) {
                    if ($binding === 'array') {
                        $stmt->execute($named ? ['value' => $value] : [$value]);
                    } else {
                        $stmt->execute();
                    }
                    checkCommandResult($stmt);
                }
            }
        }
    }

    // Native parameters retain their own encoding, independent of command text.
    $stmt = $conn->prepare("SELECT CASE WHEN CAST(? AS nvarchar(20)) = $expected THEN 1 ELSE 0 END");
    $stmt->bindParam(1, $utf8, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
    $stmt->execute();
    checkCommandResult($stmt);

    foreach ([false, true] as $emulate) {
        $binary = "\x00\xBF\x27\x00";
        $stmt = $conn->prepare(
            'SELECT CASE WHEN CAST(? AS varbinary(4)) = 0x00BF2700 THEN 1 ELSE 0 END',
            [PDO::ATTR_EMULATE_PREPARES => $emulate]
        );
        $stmt->bindParam(1, $binary, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
        $stmt->execute();
        checkCommandResult($stmt);
    }
    echo "$mode: passed\n";
}

// An explicit UTF-8 statement must override a SYSTEM connection.
$stmt = $conn->prepare(
    'SELECT CASE WHEN ' . $conn->quote($utf8) . " = $expected THEN 1 ELSE 0 END",
    [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8]
);
$stmt->execute();
checkCommandResult($stmt);
$stmt = null;
$conn = null;
echo "Statement encoding override: passed\n";
?>
--EXPECT--
default UTF-8: passed
explicit UTF-8: passed
SYSTEM: passed
Statement encoding override: passed
