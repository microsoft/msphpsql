--TEST--
PDO Test for PDO::errorCode()
--DESCRIPTION--
Verification of PDO::errorCode()
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function CheckErrorCode()
{
    include 'MsSetup.inc';

    $testName = "PDO Connection - Error Code";
    
    StartTest($testName);

    $conn1 = Connect();
    CheckError(1, $conn1, '00000');

    // Prepare test table
    $table1 = $tableName."1"; 
    $table2 = $tableName."2";
    CreateTableEx($conn1, $table1, "id int NOT NULL PRIMARY KEY, label VARCHAR(10)", null);

    // Check errors when executing SELECT queries
    $stmt1 = $conn1->prepare("SELECT id, label FROM [$table1]");
    CheckError(2, $conn1);
    CheckError(3, $stmt1);
    $stmt1->execute();
    $stmt2 = &$stmt1;
    CheckError(4, $stmt1);
    $stmt1->closeCursor();

    DropTable($conn1, $table1);
    CheckError(5, $conn1);

    // Cleanup
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);

}

function CheckError($offset, &$obj, $expected = '00000')
{
    $code = $obj->errorCode();
    if (($code != $expected) && (($expected != '00000') || ($code !='')))
    {
        printf("[%03d] Expecting error code '%s' got code '%s'\n",
            $offset, $expected, $code);
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        CheckErrorCode();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO Connection - Error Code" completed successfully.