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

include 'MsCommon.inc';

function LargeColumnNameTest($columnName, $expectfail)
{
    include 'MsSetup.inc';

    Setup();

    $conn = Connect();

    $tableName = "LargeColumnNameTest";

    DropTable($conn, $tableName);

    $conn->query("CREATE TABLE [$tableName] ([$columnName] int)");


    $conn->query("INSERT INTO [$tableName] ([$columnName]) VALUES (5)");

    $stmt = $conn->query("SELECT * from [$tableName]");

    if ( null == $stmt ) 
    {
        if (!$expectfail)
            FatalError("Possible regression: Unable to retrieve inserted value.");
    }
    
    DropTable($conn, $tableName);

}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    
    $testName = "PDO - Large Column Name Test";

    StartTest($testName);

    $columnName = "是";

    try
    {
        for ($a = 1; $a <= 128; $a++)
        {
            LargeColumnNameTest($columnName, $a > 128);
            $columnName .= "是";
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
Test "PDO - Large Column Name Test" completed successfully.
