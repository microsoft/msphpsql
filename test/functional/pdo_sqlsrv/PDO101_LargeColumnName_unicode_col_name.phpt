--TEST--
PDO - Large Unicode Column Name Test
--DESCRIPTION--
Verifies that long column names in Unicode are supported (up to 128 chars).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function largeColumnNameTest($columnName)
{
    include 'MsSetup.inc';

    Setup();

    $conn = connect();

    $tableName = "largeColumnNameTest";

    dropTable($conn, $tableName);

    $conn->query("CREATE TABLE [$tableName] ([$columnName] int)");

    $conn->query("INSERT INTO [$tableName] ([$columnName]) VALUES (5)");

    $stmt = $conn->query("SELECT * from [$tableName]");

    dropTable($conn, $tableName);
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    $testName = "PDO - Large Column Name Test";

    startTest($testName);

    // The maximum size of a column name is 128 characters 
    $maxlen = 128;
    $columnName = str_repeat('是', $maxlen);

    try {
        largeColumnNameTest($columnName);
    } catch (Exception $e) {
        echo "Possible regression: Unable to retrieve inserted value\n";
        print_r($e->getMessage());
        echo "\n";
    }

    // Now add another character to the name
    $columnName .= '是';
    try {
        largeColumnNameTest($columnName);
    } catch (Exception $e) {
        // Expects to fail 
        $expected = 'is too long. Maximum length is 128.';
        if (strpos($e->getMessage(), $expected) === false) {
            print_r($e->getMessage());
            echo "\n";
        }
    }

    endTest($testName);
}

repro();

?>
--EXPECT--
Test "PDO - Large Column Name Test" completed successfully.
