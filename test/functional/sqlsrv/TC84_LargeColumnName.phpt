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

function LargeColumnNameTest($columnName, $expectfail)
{
    setup();

    $conn = connect();

    $tableName = "LargeColumnNameTest";

    dropTable($conn, $tableName);

    sqlsrv_query($conn, "CREATE TABLE [$tableName] ([$columnName] int)");

    sqlsrv_query($conn, "INSERT INTO [$tableName] ([$columnName]) VALUES (5)");

    $stmt = sqlsrv_query($conn, "SELECT * from [$tableName]");

    if (null == $stmt) {
        echo  "$";
        echo  "stmt = null";
        echo  "\n";
    } else {
        if (null == sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!$expectfail) {
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

    $columnName = "a";

    try {
        for ($a = 1; $a <= 129; $a++) {
            LargeColumnNameTest($columnName, $a > 128);
            $columnName .= "A";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }


    endTest($testName);
}

repro();
?>
--EXPECT--
$stmt = null
Test "PHP - Large Column Name Test" completed successfully.
