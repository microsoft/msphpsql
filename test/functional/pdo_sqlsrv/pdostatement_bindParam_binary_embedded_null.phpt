--TEST--
PDOStatement::bindParam binary data with embedded NUL bytes is not truncated (emulated prepares)
--DESCRIPTION--
Regression test for the NUL-byte truncation issue (CWE-626) in pdo_sqlsrv_dbh_quote.
With PDO::ATTR_EMULATE_PREPARES enabled and PDO::SQLSRV_ENCODING_BINARY, PDO::quote builds
the hex SQL literal client-side. Binary parameters that contain embedded NUL (0x00) bytes
must be fully hex-encoded rather than being silently truncated at the first NUL byte.
Mirrors pdostatement_bindParam_empty_binary.phpt.
--SKIPIF--
<?php
require('skipif_mid-refactor.inc');
// Emulated prepares (required to exercise the PDO::quote hex path) are not
// supported on a Column Encryption (Always Encrypted) enabled connection.
if (isAEConnected()) {
    die("skip Emulated prepares are not supported with Column Encryption enabled");
}
?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    // 11-byte binary value with an embedded NUL after the 5th byte.
    // Before the fix, only the "hello" prefix (5 bytes) survived the hex encoding.
    $binary = "hello\x00world";
    $expectedLen = strlen($binary);         // 11

    // 1) DATALENGTH via emulated prepare must report the full length, not 5.
    $stmt = $conn->prepare("SELECT DATALENGTH(?)", array(PDO::ATTR_EMULATE_PREPARES => true));
    $stmt->bindParam(1, $binary, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->execute();
    $len = intval($stmt->fetchColumn());
    if ($len !== $expectedLen) {
        echo "Unexpected DATALENGTH: got $len, expected $expectedLen\n";
    }

    // 2) Round-trip the value through a varbinary column and compare byte-for-byte.
    $tableName = "pdoBinaryEmbeddedNull";
    $colMetaArr = array(new ColumnMeta("varbinary(16)", "VarBinaryCol"));
    createTable($conn, $tableName, $colMetaArr);

    $insert = $conn->prepare("INSERT INTO $tableName (VarBinaryCol) VALUES (?)", array(PDO::ATTR_EMULATE_PREPARES => true));
    $insert->bindParam(1, $binary, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $insert->execute();

    $select = $conn->query("SELECT VarBinaryCol, DATALENGTH(VarBinaryCol) AS Len FROM $tableName");
    $row = $select->fetch(PDO::FETCH_NUM);
    if (intval($row[1]) !== $expectedLen) {
        echo "Unexpected stored DATALENGTH: got $row[1], expected $expectedLen\n";
    }
    if ($row[0] !== $binary) {
        echo "Round-tripped binary does not match original\n";
        var_dump(bin2hex($row[0]));
    }

    dropTable($conn, $tableName);
    unset($select);
    unset($insert);
    unset($stmt);
    unset($conn);

    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
Done
