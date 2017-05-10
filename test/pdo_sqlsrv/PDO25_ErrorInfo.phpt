--TEST--
PDO Test for PDO::errorInfo()
--DESCRIPTION--
Verification of PDO::errorInfo()
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function CheckErrorInfo()
{
    include 'MsSetup.inc';

    $testName = "PDO Connection - Error Info";
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


    //DropTable($conn1, $table1);
    @$stmt1->execute();
    CheckError(5, $conn1);
    //CheckError(6, $stmt1, '42S02');
    //CheckError(7, $stmt2, '42S02');
    $stmt1->closeCursor();
    
    DropTable($conn1, $table2);
    $conn2 = &$conn1;
    //@$conn1->query("SELECT id, label FROM [$table2]");
    //CheckError(8, $conn1, '42S02');
    //CheckError(9, $conn2, '42S02');
    

    CreateTableEx($conn1, $table1, "id int NOT NULL PRIMARY KEY, label VARCHAR(10)", null);
    $stmt1 = $conn1->query("SELECT id, label FROM [$table1]");
    CheckError(10, $conn1);
    CheckError(11, $stmt1);
    $stmt1->closeCursor();

//  @$conn1->query("SELECT id, label FROM [$table2]");
//  CheckError(12, $conn1, '42S02');
//  CheckError(13, $conn2, '42S02');
    CheckError(14, $stmt1);

    // Cleanup
    DropTable($conn1, $table1);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}


function CheckError($offset, &$obj, $expected = '00000')
{
    $info = $obj->errorInfo();
    $code = $info[0];

    if (($code != $expected) && (($expected != '00000') || ($code != '')))
    {
        printf("[%03d] Expecting error code '%s' got code '%s'\n",
            $offset, $expected, $code);
    }
    if ($expected != '00000')
    {
        if (!isset($info[1]) || ($info[1] == ''))
        {
            printf("[%03d] Driver-specific error code not set\n", $offset);
        }
        if (!isset($info[2]) || ($info[2] == ''))
        {
            printf("[%03d] Driver-specific error message.not set\n", $offset);
        }
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
        CheckErrorInfo();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO Connection - Error Info" completed successfully.