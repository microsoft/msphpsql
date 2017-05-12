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

    $testName = "Complex Insert Query";

    StartTest($testName);

    Setup();

    $conn1 = Connect();

    DropTable($conn1, $tableName);
    
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
    while (sqlsrv_next_result($stmt1) != NULL) {};
    sqlsrv_free_stmt($stmt1);

    $stmt2 = ExecuteQuery($conn1, $querySelect);
    $row = sqlsrv_fetch_array($stmt2);
    sqlsrv_free_stmt($stmt2);

    printf("$count rows attempted; actual rows created = ".$row[0]."\n");

    sqlsrv_close($conn1);

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
        ComplexInsert(160);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
160 rows attempted; actual rows created = 160
Test "Complex Insert Query" completed successfully.


