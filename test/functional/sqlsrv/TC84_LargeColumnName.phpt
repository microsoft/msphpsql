--TEST--
PHP - Large Column Name Test
--DESCRIPTION--
Verifies that long column names are supported (up to 128 chars).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function largeColumnNameTest($columnName, $expectFail = false)
{
    setup();

    $conn = connect();

    $tableName = "LargeColumnNameTest";

    dropTable($conn, $tableName);

    $stmt = sqlsrv_query($conn, "CREATE TABLE [$tableName] ([$columnName] int)");
    if ($stmt == null) {
        if (!$expectFail) {
            fatalError("Possible regression: Unable to create test $tableName.");
        } else {
            $expected = 'is too long. Maximum length is 128.';
            if (strpos(sqlsrv_errors()[0]['message'], $expected) === false) {
                print_r(sqlsrv_errors());
            }
            echo  "$";
            echo  "stmt = null";
            echo  "\n";
        }
    } else {
        sqlsrv_query($conn, "INSERT INTO [$tableName] ([$columnName]) VALUES (5)");

        $stmt = sqlsrv_query($conn, "SELECT * from [$tableName]");

        if (null == sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!$expectFail) {
                fatalError("Possible regression: Unable to retrieve inserted value.");
            }
        }
        sqlsrv_free_stmt($stmt);
    }

    dropTable($conn, $tableName);

    sqlsrv_close($conn);
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    $testName = "PHP - Large Column Name Test";

    startTest($testName);

    // The maximum size of a column name is 128 characters 
    $maxlen = 128;
    $columnName = str_repeat('a', $maxlen);

    largeColumnNameTest($columnName);
    
    // Now add another character to the name
    $columnName .= "A";
    
    largeColumnNameTest($columnName, true);

    endTest($testName);
}

repro();
?>
--EXPECT--
$stmt = null
Test "PHP - Large Column Name Test" completed successfully.
