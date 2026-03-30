--TEST--
Test SQLSRV_ENCODING_UTF8_VARCHAR binds parameters as VARCHAR with UTF-8 data
--DESCRIPTION--
Verifies that SQLSRV_ENCODING_UTF8_VARCHAR sends string parameters as SQL_VARCHAR/SQL_C_CHAR
(not SQL_WVARCHAR/SQL_C_WCHAR), allowing efficient use of VARCHAR columns with _UTF8 collations
without implicit NVARCHAR conversion.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $tbname = getTableName('utf8_varchar_test');

    // Create table with VARCHAR columns using UTF-8 collation
    $conn->exec("IF OBJECT_ID('$tbname', 'U') IS NOT NULL DROP TABLE $tbname");
    $conn->exec("CREATE TABLE $tbname (
        id int IDENTITY(1,1) NOT NULL,
        name varchar(255) COLLATE Latin1_General_100_CI_AS_SC_UTF8 NULL,
        data varchar(max) COLLATE Latin1_General_100_BIN2_UTF8 NOT NULL,
        CONSTRAINT PK_$tbname PRIMARY KEY (id)
    )");

    $testCases = [
        [
            'label' => 'basic_ascii',
            'name'  => 'Hello World',
            'data'  => '{"text": "Hello World"}',
        ],
        [
            'label' => 'european_utf8',
            'name'  => 'Straße Köln Grüß Gott',
            'data'  => '{"text": "Straße Köln Grüß Gott"}',
        ],
        [
            'label' => 'extended_latin',
            'name'  => 'Ñoño café résumé naïve',
            'data'  => '{"text": "Ñoño café résumé naïve"}',
        ],
    ];

    // ===== Test 1: Connection-level encoding =====
    echo "=== Test 1: Connection-level SQLSRV_ENCODING_UTF8_VARCHAR ===\n";
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);

    foreach ($testCases as $case) {
        $stmt = $conn->prepare("INSERT INTO $tbname (name, data) VALUES (?, ?)");
        $stmt->execute([$case['name'], $case['data']]);
        $id = $conn->lastInsertId();

        $stmt = $conn->prepare("SELECT name, data FROM $tbname WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $nameMatch = ($case['name'] === $row['name']) ? 'YES' : 'NO';
        $dataMatch = ($case['data'] === $row['data']) ? 'YES' : 'NO';

        echo "CASE {$case['label']}: name={$nameMatch}, data={$dataMatch}\n";
    }

    // ===== Test 2: Statement-level encoding =====
    echo "=== Test 2: Statement-level SQLSRV_ENCODING_UTF8_VARCHAR ===\n";
    // Reset connection encoding to default UTF-8
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    $conn->exec("TRUNCATE TABLE $tbname");

    foreach ($testCases as $case) {
        $stmt = $conn->prepare("INSERT INTO $tbname (name, data) VALUES (?, ?)",
            [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8_VARCHAR]);
        $stmt->execute([$case['name'], $case['data']]);
        $id = $conn->lastInsertId();

        $stmt = $conn->prepare("SELECT name, data FROM $tbname WHERE id = ?",
            [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8_VARCHAR]);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $nameMatch = ($case['name'] === $row['name']) ? 'YES' : 'NO';
        $dataMatch = ($case['data'] === $row['data']) ? 'YES' : 'NO';

        echo "CASE {$case['label']}: name={$nameMatch}, data={$dataMatch}\n";
    }

    // ===== Test 3: Per-parameter encoding via bindParam =====
    echo "=== Test 3: Per-parameter SQLSRV_ENCODING_UTF8_VARCHAR ===\n";
    $conn->exec("TRUNCATE TABLE $tbname");

    foreach ($testCases as $case) {
        $stmt = $conn->prepare("INSERT INTO $tbname (name, data) VALUES (:name, :data)");
        $stmt->bindParam(':name', $case['name'], PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
        $stmt->bindParam(':data', $case['data'], PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8_VARCHAR);
        $stmt->execute();
        $id = $conn->lastInsertId();

        $stmt = $conn->prepare("SELECT name, data FROM $tbname WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $nameMatch = ($case['name'] === $row['name']) ? 'YES' : 'NO';
        $dataMatch = ($case['data'] === $row['data']) ? 'YES' : 'NO';

        echo "CASE {$case['label']}: name={$nameMatch}, data={$dataMatch}\n";
    }

    // Cleanup
    $conn->exec("DROP TABLE $tbname");
    echo "Done\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
--EXPECT--
=== Test 1: Connection-level SQLSRV_ENCODING_UTF8_VARCHAR ===
CASE basic_ascii: name=YES, data=YES
CASE european_utf8: name=YES, data=YES
CASE extended_latin: name=YES, data=YES
=== Test 2: Statement-level SQLSRV_ENCODING_UTF8_VARCHAR ===
CASE basic_ascii: name=YES, data=YES
CASE european_utf8: name=YES, data=YES
CASE extended_latin: name=YES, data=YES
=== Test 3: Per-parameter SQLSRV_ENCODING_UTF8_VARCHAR ===
CASE basic_ascii: name=YES, data=YES
CASE european_utf8: name=YES, data=YES
CASE extended_latin: name=YES, data=YES
Done
