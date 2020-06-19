--TEST--
Test inserting and retrieving UTF-8 text 
--DESCRIPTION--
This is similar to sqlsrv 0065.phpt with checking for error conditions concerning encoding issues.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function verifyColumnData($columns, $results, $utf8)
{
    for ($i = 0; $i < count($columns); $i++) {
        if ($i > 0) {
            if ($results[$i] !== $utf8) {
                echo $columns[$i]->colName . ' does not match the inserted UTF-8 text';
                var_dump($results[$i]);
            }
        } else {
            // The first column, a varchar(100) column, should have question marks,
            // like this one:
            $expected = "So?e sä???? ?SCII-te×t";
            // With AE, the fetched result may be different in Windows and other 
            // platforms -- the point is to check if there are some '?'
            if (!isAEConnected() && $results[$i] !== $expected) {
                echo $columns[$i]->colName . " does not match $expected";
                var_dump($results[$i]);
            } else {
                $arr = explode('?', $results[$i]);
                // in Alpine Linux, data returned is diffferent with always encrypted:
                // something like '**** *ä**** *****-**×*'
                // instead of '?', it replaces inexact conversions with asterisks
                // reference: read the ICONV section in
                // https://wiki.musl-libc.org/functional-differences-from-glibc.html
                if (count($arr) == 1) {
                    // this means there is no question mark in $t
                    // then try to find a substring of some asterisks
                    $asterisks = '****';
                    if(strpos($results[$i], '****') === false) {
                        echo $columns[$i]->colName . " value is unexpected";
                        var_dump($results[$i]);
                    }
                }
            }
        }
    }
}

function dropProcedures($conn)
{
    // Drop all procedures
    dropProc($conn, "pdoIntDoubleProc");
    dropProc($conn, "pdoUTF8OutProc");
    dropProc($conn, "pdoUTF8OutWithResultsetProc");
    dropProc($conn, "pdoUTF8InOutProc");
}

function createProcedures($conn, $tableName)
{
    // Drop all procedures first
    dropProcedures($conn);
    
    $createProc = <<<PROC
CREATE PROCEDURE pdoUTF8OutProc
    @param nvarchar(25) OUTPUT
AS
BEGIN
    set @param = convert(nvarchar(25), 0x5E01A1013C04170120005B01E400DD1040044001C11E200086035A010801280130012D0065012E21D7006701);
END;
PROC;
    $stmt = $conn->query($createProc);
    
    $createProc = "CREATE PROCEDURE pdoUTF8OutWithResultsetProc @param NVARCHAR(25) OUTPUT AS BEGIN SELECT c1, c2, c3 FROM $tableName SET @param = CONVERT(NVARCHAR(25), 0x5E01A1013C04170120005B01E400DD1040044001C11E200086035A010801280130012D0065012E21D7006701); END";
    $stmt = $conn->query($createProc);

    $createProc = "CREATE PROCEDURE pdoUTF8InOutProc @param NVARCHAR(25) OUTPUT AS BEGIN SET @param = CONVERT(NVARCHAR(25), 0x6001E11EDD10130120006101E200DD1040043A01BB1E2000C5005A01C700CF0007042D006501BF1E45046301); END";
    $stmt = $conn->query($createProc);

    $createProc = "CREATE PROCEDURE pdoIntDoubleProc @param INT OUTPUT AS BEGIN SET @param = @param + @param; END;";
    $stmt = $conn->query($createProc);
}

function runBaselineProc($conn)
{
    $sql = "{call pdoIntDoubleProc(?)}";
    $val = 1;
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $val, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);
    $stmt->execute();
    
    if ($val !== 2) {
        echo "Incorrect value $val for pdoIntDoubleProc\n";
    }
}

function runImmediateConversion($conn, $utf8)
{
    $sql = "{call pdoUTF8OutProc(?)}";
    $val = '';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $val, PDO::PARAM_STR, 50);
    $stmt->execute();
    
    if ($val !== $utf8) {
        echo "Incorrect value $val for pdoUTF8OutProc\n";
    }
}

function runProcWithResultset($conn, $utf8)
{
    $sql = "{call pdoUTF8OutWithResultsetProc(?)}";
    $val = '';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $val, PDO::PARAM_STR, 50);
    $stmt->execute();
    
    // Moves the cursor to the next result set
    $stmt->nextRowset();
    
    if ($val !== $utf8) {
        echo "Incorrect value $val for pdoUTF8OutWithResultsetProc\n";
    }
}

function runInOutProcWithErrors($conn, $utf8_2)
{
    // The input string is smaller than the output size for testing
    $val = 'This is a test.';
    
    // The following should work
    $sql = "{call pdoUTF8InOutProc(?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $val, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 25);
    $stmt->execute();

    if ($val !== $utf8_2) {
        echo "Incorrect value $val for pdoUTF8InOutProc Part 1\n";
    }

    // Use a much longer input string
    $val = 'This is a longer test that exceeds the returned values buffer size so that we can test an input buffer size larger than the output buffer size.';
    try {
        $stmt->bindParam(1, $val, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 25);
        $stmt->execute();
        echo "Should have failed since the string is too long!\n";
    } catch (PDOException $e) {
        $error = '*String data, right truncation';
        if ($e->getCode() !== "22001" || !fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
}

function runIntDoubleProcWithErrors($conn)
{
    $sql = "{call pdoUTF8InOutProc(?)}";
    $val = pack('H*', 'ffffffff');

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $val, PDO::PARAM_STR);
        $stmt->execute();
        echo "Should have failed because of an invalid utf-8 string!\n";
    } catch (PDOException $e) {
        $error = '*An error occurred translating string for input param 1 to UCS-2:*';
        if ($e->getCode() !== "IMSSP" || !fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
}

try {
    $conn = connect();
    
    // Create test table
    $tableName = 'pdoUTF8test';
    $columns = array(new ColumnMeta('varchar(100)', 'c1'),
                     new ColumnMeta('nvarchar(100)', 'c2'),
                     new ColumnMeta('nvarchar(max)', 'c3'));
    $stmt = createTable($conn, $tableName, $columns);
    
    $utf8 = "Şơмė śäოрŀề ΆŚĈĨİ-ť℮×ŧ";

    $insertSql = "INSERT INTO $tableName (c1, c2, c3) VALUES (?, ?, ?)";
    $stmt1 = $conn->prepare($insertSql);
    $stmt1->bindParam(1, $utf8);
    $stmt1->bindParam(2, $utf8);
    $stmt1->bindParam(3, $utf8);
    
    $stmt1->execute();

    $stmt2 = $conn->prepare("SELECT c1, c2, c3 FROM $tableName");
    $stmt2->execute();
    $results = $stmt2->fetch(PDO::FETCH_NUM);
    verifyColumnData($columns, $results, $utf8);

    // Start creating stored procedures for testing
    createProcedures($conn, $tableName);
    
    runBaselineProc($conn);
    runImmediateConversion($conn, $utf8);
    runProcWithResultset($conn, $utf8);

    // Use another set of UTF-8 text to test
    $utf8_2 = "Šỡოē šâოрĺẻ ÅŚÇÏЇ-ťếхţ";
    runInOutProcWithErrors($conn, $utf8_2);

    // Now insert second row
    $utf8_3 = pack('H*', '7a61cc86c7bdceb2f18fb3bf');
    $stmt1->bindParam(1, $utf8_3);
    $stmt1->bindParam(2, $utf8_3);
    $stmt1->bindParam(3, $utf8_3);
    $stmt1->execute();
    
    // Fetch data, ignoring first row
    $stmt2->execute();
    $stmt2->fetch(PDO::FETCH_NUM);

    // Move to the second row and check second field 
    $results2 = $stmt2->fetch(PDO::FETCH_NUM);
    if ($results2[1] !== $utf8_3) {
        echo "Unexpected $results2[1] from field 2 in second row.\n";
    }

    // Last test with an invalid input
    runIntDoubleProcWithErrors($conn);
    
    echo "Done\n";
    
    // Done testing with stored procedures and table
    dropProcedures($conn);
    dropTable($conn, $tableName);
    
    unset($stmt1);
    unset($stmt2);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Done