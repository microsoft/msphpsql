--TEST--
GitHub issue 1018 - Test real prepared statements with the extended string types 
--DESCRIPTION--
This test verifies the extended string types, PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_NATL and
PDO::PARAM_STR_CHAR will NOT affect real prepared statements. Unlike emulate prepared statements,
real prepared statements will only be affected by the parameter encoding. If not set, it will use
the statement encoding or the connection one, which is by default UTF-8.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_old_php.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

$p = '銀河galaxy';
$p1 = '??galaxy';
$tableName = 'test1018';

// in Alpine Linux, instead of '?', it replaces inexact conversions with asterisks
// reference: read the ICONV section in
// https://wiki.musl-libc.org/functional-differences-from-glibc.html
$p2 = '**galaxy';

function insertRead($conn, $pdoStrParam, $value, $testCase, $id, $encoding = false)
{
    global $p, $tableName;
    global $p1, $p2;

    $sql = "INSERT INTO $tableName (Col1) VALUES (:value)";
    $options = array(PDO::ATTR_EMULATE_PREPARES => false);  // it's false by default anyway
    $stmt = $conn->prepare($sql, $options);

    // Set param encoding only if $encoding is NOT FALSE
    if ($encoding !== false) {
        $stmt->bindParam(':value', $p, $pdoStrParam, 0, $encoding);
        $encOptions = array(PDO::SQLSRV_ATTR_ENCODING => $encoding);
    } else {
        $stmt->bindParam(':value', $p, $pdoStrParam);
        $encOptions = array();
    }
    $stmt->execute();

    // Should also set statement encoding when $encoding is NOT FALSE
    // such that data can be fetched with the right encoding
    $sql = "SELECT Col1 FROM $tableName WHERE ID = $id";
    $stmt = $conn->prepare($sql, $encOptions);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_NUM);
    trace("$testCase: expected $value and returned $result[0]\n");
    if ($result[0] !== $value) {
        // Also check the other exception
        if ($value === $p1 && $result[0] !== $p2) {
            echo("$testCase: expected $value or $p2 but returned:\n");
            var_dump($result);
        }
    }
}

function testUTF8encoding($conn)
{
    global $p, $tableName;

    // Create a NVARCHAR column
    $sql = "CREATE TABLE $tableName (ID int identity(1,1), Col1 NVARCHAR(100))";
    $conn->query($sql);

    // The extended string types PDO::PARAM_STR_NATL and PDO::PARAM_STR_CHAR
    // will be ignored in the following test cases. Only the statement or
    // the connection encoding matters.

    // Test case 1: PDO::PARAM_STR_CHAR
    $testCase = 'UTF-8 case 1: no default but specifies PDO::PARAM_STR_CHAR';
    insertRead($conn, PDO::PARAM_STR | PDO::PARAM_STR_CHAR, $p, $testCase, 1);

    // Test case 2: PDO::PARAM_STR_NATL
    $testCase = 'UTF-8 case 2: no default but specifies PDO::PARAM_STR_NATL';
    insertRead($conn, PDO::PARAM_STR | PDO::PARAM_STR_NATL, $p, $testCase, 2);

    // Test case 3: no extended string types
    $testCase = 'UTF-8 case 3: no default but no extended string types either';
    insertRead($conn, PDO::PARAM_STR, $p, $testCase, 3);

    // Test case 4: no extended string types but specifies UTF-8 encoding
    $testCase = 'UTF-8 case 4: no default but no extended string types but with UTF-8 encoding';
    insertRead($conn, PDO::PARAM_STR, $p, $testCase, 4, PDO::SQLSRV_ENCODING_UTF8);

    dropTable($conn, $tableName);
}

function testNonUTF8encoding($conn)
{
    global $p, $p1, $tableName;

    // Create a VARCHAR column
    $sql = "CREATE TABLE $tableName (ID int identity(1,1), Col1 VARCHAR(100))";
    $conn->query($sql);

    // The extended string types PDO::PARAM_STR_NATL and PDO::PARAM_STR_CHAR
    // will be ignored in the following test cases. Only the statement or
    // the connection encoding matters.

    // Test case 1: PDO::PARAM_STR_CHAR (expect $p1)
    $testCase = 'System case 1: no default but specifies PDO::PARAM_STR_CHAR';
    insertRead($conn, PDO::PARAM_STR | PDO::PARAM_STR_CHAR, $p1, $testCase, 1);

    // Test case 2: PDO::PARAM_STR_NATL (expect $p1)
    $testCase = 'System case 2: no default but specifies PDO::PARAM_STR_NATL';
    insertRead($conn, PDO::PARAM_STR | PDO::PARAM_STR_NATL, $p1, $testCase, 2);

    // Test case 3: no extended string types (expect $p1)
    $testCase = 'System case 3: no default but no extended string types either';
    insertRead($conn, PDO::PARAM_STR, $p1, $testCase, 3);

    // Test case 4: no extended string types but specifies UTF-8 encoding (expect $p1)
    $testCase = 'System case 4: no default but no extended string types but with UTF-8 encoding';
    insertRead($conn, PDO::PARAM_STR, $p1, $testCase, 4, PDO::SQLSRV_ENCODING_UTF8);

    dropTable($conn, $tableName);
}

try {
    $conn = connect();
    dropTable($conn, $tableName);

    // The connection encoding is by default PDO::SQLSRV_ENCODING_UTF8. For this test
    // no change is made to the connection encoding.
    testUTF8encoding($conn);
    testNonUTF8encoding($conn);

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Done
