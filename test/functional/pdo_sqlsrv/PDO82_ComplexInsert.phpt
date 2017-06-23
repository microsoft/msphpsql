--TEST--
Complex Insert Query Test
--DESCRIPTION--
Test the driver behavior with a complex insert query.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ComplexInsert($count)
{
        include 'MsSetup.inc';

    $testName = "PDO - Complex Insert Query";

    StartTest($testName);

    Setup();

    $conn1 = Connect();

    DropTable($conn1, $tableName);

//  $data = "a1='1234567890',a2='0987654321',a3='1234567890',a4='0987654321',a5='1234567890',a6='0987654321'";
    $data = "a1='1', a2='2', a3='3', a4='4', a5='5', a6='6'";
    $querySelect = "SELECT COUNT(*) FROM [$tableName]";
    $queryInsert =
    "   SELECT $data INTO [$tableName]
    
        DECLARE @i int
        SET @i=1
        WHILE (@i < $count)
        BEGIN
            INSERT [$tableName]
            SELECT $data
            SET @i = @i + 1
        END
    ";

    $stmt1 = ExecuteQuery($conn1, $queryInsert);
    $stmt1->closeCursor();
    
    $stmt1 = null;

    $stmt2 = ExecuteQuery($conn1, $querySelect);
    $row = $stmt2->fetch(PDO::FETCH_NUM);
    printf("$count rows attempted; actual rows created = ".$row[0]."\n");

    $stmt2 = null;
    $conn1 = null;

    EndTest($testName); 
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        ComplexInsert(1000);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
1000 rows attempted; actual rows created = 1000
Test "PDO - Complex Insert Query" completed successfully.


