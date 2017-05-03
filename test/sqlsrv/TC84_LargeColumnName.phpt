--TEST--
PHP - Large Column Name Test
--Description--
Verifies that long column names are supported (up to 128 chars).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function LargeColumnNameTest($columnName, $expectfail)
{
    include 'MsSetup.inc';

    Setup();

    $conn = Connect();

    $tableName = "LargeColumnNameTest";

    DropTable($conn, $tableName);

    sqlsrv_query($conn, "CREATE TABLE [$tableName] ([$columnName] int)");

    sqlsrv_query($conn, "INSERT INTO [$tableName] ([$columnName]) VALUES (5)");

    $stmt = sqlsrv_query($conn, "SELECT * from [$tableName]");

    if ( null == $stmt )
    {
        echo  "$";
        echo  "stmt = null";
        echo  "\n";
    }
    else 
    {
        if ( null == sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ) 
        {
            if (!$expectfail)
                FatalError("Possible regression: Unable to retrieve inserted value.");
        }
        sqlsrv_free_stmt($stmt);
    }


    DropTable($conn, $tableName);

    sqlsrv_close($conn);

}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    
    $testName = "PHP - Large Column Name Test";

    StartTest($testName);

    $columnName = "a";

    try
    {
        for ($a = 1; $a <= 129; $a++)
        {
            LargeColumnNameTest($columnName, $a > 128);
            $columnName .= "A";
        }
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }

    
    EndTest($testName);
}

Repro();
?>
--EXPECT--
$stmt = null
Test "PHP - Large Column Name Test" completed successfully.
