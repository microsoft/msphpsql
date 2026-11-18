--TEST--
PDO preserves default modes, unsupported persistence, and statement ownership after errors
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require('MsSetup.inc');
$dsn = "sqlsrv:Server=$server;Database=$databaseName;Driver=$driver;Encrypt=$encrypt;ConnectionPooling=false";
try {
    $persistent = new PDO($dsn, $uid, $pwd, array(PDO::ATTR_PERSISTENT => true));
    throw new Exception('Persistent connections must remain unsupported.');
} catch (PDOException $e) {
    echo "Persistence rejected\n";
}
$conn = new PDO($dsn, $uid, $pwd);
if ($conn->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION
    || $conn->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE) !== PDO::FETCH_BOTH) {
    throw new Exception('Default PDO modes changed.');
}
$stmt = $conn->query("SELECT CAST('ok' AS varchar(2)) AS value");
if ($stmt->fetch() !== array('value' => 'ok', 0 => 'ok')) {
    throw new Exception('Default fetch mode changed.');
}
unset($stmt);
echo "Defaults preserved\n";
foreach (array(PDO::ERRMODE_SILENT, PDO::ERRMODE_WARNING, PDO::ERRMODE_EXCEPTION) as $mode) {
    $conn->setAttribute(PDO::ATTR_ERRMODE, $mode);
    for ($iteration = 0; $iteration < 3; ++$iteration) {
        $warnings = 0;
        $exceptions = 0;
        set_error_handler(function ($severity) use (&$warnings) {
            if ($severity !== E_WARNING) {
                return false;
            }
            ++$warnings;
            return true;
        });
        try {
            $failed = $conn->query('SELECT FROM');
            if ($failed !== false) {
                throw new Exception('Invalid query unexpectedly succeeded.');
            }
        } catch (PDOException $e) {
            ++$exceptions;
        } finally {
            restore_error_handler();
        }
        if ($warnings !== ($mode === PDO::ERRMODE_WARNING ? 1 : 0)
            || $exceptions !== ($mode === PDO::ERRMODE_EXCEPTION ? 1 : 0)
            || $conn->errorCode() === '00000') {
            throw new Exception('Incorrect PDO error behavior.');
        }
        $stmt = $conn->query("SELECT CAST('reused' AS varchar(6)) AS value");
        if ($stmt->fetch(PDO::FETCH_ASSOC) !== array('value' => 'reused')
            || $conn->errorCode() !== '00000') {
            throw new Exception('Connection not reset after failed query.');
        }
        unset($stmt);
    }
    echo "Error mode $mode: passed\n";
}
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $conn->prepare('SELECT CAST(? AS int)');
try {
    $stmt->execute(array('not_an_integer'));
    $stmt->fetchColumn();
    throw new Exception('Invalid conversion unexpectedly succeeded.');
} catch (PDOException $e) {
    echo "Statement error caught\n";
}
$stmt->closeCursor();
$stmt->execute(array(42));
if ((int) $stmt->fetchColumn() !== 42 || $stmt->errorCode() !== '00000') {
    throw new Exception('Prepared statement reuse failed.');
}
unset($stmt, $conn);
echo "Statement reused\n";
?>
--EXPECT--
Persistence rejected
Defaults preserved
Error mode 0: passed
Error mode 1: passed
Error mode 2: passed
Statement error caught
Statement reused
