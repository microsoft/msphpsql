--TEST--
Test SQLSRV_ENCODING_UTF8_VARCHAR with bound columns, emulate prepares, and output params
--DESCRIPTION--
Covers additional code paths for SQLSRV_ENCODING_UTF8_VARCHAR:
- bindColumn with the new encoding
- Emulate prepares (PDO::ATTR_EMULATE_PREPARES)
- Output parameters from stored procedures
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $tbname = getTableName('utf8vc_extra');
    $procName = getTableName('sp_utf8vc_out');

    $conn->exec("IF OBJECT_ID('$tbname', 'U') IS NOT NULL DROP TABLE $tbname");
    $conn->exec("CREATE TABLE $tbname (
        id int IDENTITY(1,1) NOT NULL,
        name varchar(255) COLLATE Latin1_General_100_CI_AS_SC_UTF8 NULL,
        CONSTRAINT PK_$tbname PRIMARY KEY (id)
    )");

    $testVal = 'Straße Köln Grüß Gott';

    // Insert test data using the new encoding
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
    $stmt = $conn->prepare("INSERT INTO $tbname (name) VALUES (?)");
    $stmt->execute([$testVal]);

    // === Test 1: bindColumn with SQLSRV_ENCODING_UTF8_VARCHAR ===
    echo "=== Test 1: bindColumn ===\n";
    $stmt = $conn->prepare("SELECT name FROM $tbname WHERE id = 1");
    $stmt->execute();
    $stmt->bindColumn('name', $nameOut, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
    $stmt->fetch(PDO::FETCH_BOUND);
    echo "bindColumn: " . (($nameOut === $testVal) ? 'PASS' : 'FAIL') . "\n";

    // === Test 2: emulate prepares (ASCII data — emulate prepares with non-ASCII ===
    // data requires the database default collation to be UTF-8)
    echo "=== Test 2: Emulate prepares ===\n";
    $asciiVal = 'Hello World Test';
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);

    $stmt = $conn->prepare("INSERT INTO $tbname (name) VALUES (?)");
    $stmt->execute([$asciiVal]);
    $id = $conn->lastInsertId();

    // Read back (turn off emulate for SELECT)
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $stmt = $conn->prepare("SELECT name FROM $tbname WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "emulate: " . (($row['name'] === $asciiVal) ? 'PASS' : 'FAIL') . "\n";

    // === Test 3: output parameter ===
    echo "=== Test 3: Output param ===\n";
    $conn->exec("IF OBJECT_ID('$procName', 'P') IS NOT NULL DROP PROCEDURE $procName");
    $conn->exec("CREATE PROCEDURE $procName @id INT, @out_name VARCHAR(255) OUTPUT
    AS
    BEGIN
        SELECT @out_name = name FROM $tbname WHERE id = @id
    END");

    $outName = str_repeat(' ', 255);
    $stmt = $conn->prepare("EXEC $procName ?, ?");
    $stmt->bindValue(1, 1, PDO::PARAM_INT);
    $stmt->bindParam(2, $outName, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 255, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
    $stmt->execute();

    $outName = rtrim($outName);
    echo "output: " . (($outName === $testVal) ? 'PASS' : 'FAIL') . "\n";

    // Cleanup
    $conn->exec("DROP PROCEDURE $procName");
    $conn->exec("DROP TABLE $tbname");
    echo "Done\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
--EXPECT--
=== Test 1: bindColumn ===
bindColumn: PASS
=== Test 2: Emulate prepares ===
emulate: PASS
=== Test 3: Output param ===
output: PASS
Done
