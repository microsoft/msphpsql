--TEST--
Delete Query Test
--DESCRIPTION--
Executes several INSERT queries followed by DELETE queries and
validates the outcome reported by "sqlsrv_rows_affected".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function DeleteQuery()
{
    include 'MsSetup.inc';

    $testName = "Statement - Delete Query";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $noRows = 10;
    CreateTable($conn1, $tableName);
    $noRowsInserted = InsertRows($conn1, $tableName, $noRows);

    $row = 1;
    $keyValue = "0";
    while ($row <= $noRowsInserted)
    {
        $stmt1 = SelectFromTable($conn1, $tableName);
        if (sqlsrv_fetch($stmt1) === false)
        {
            FatalError("Failed to retrieve 1st row of data from test table");
        }
        $keyValue = sqlsrv_get_field($stmt1, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        sqlsrv_free_stmt($stmt1);

        Trace("Deleting rows from $tableName ...");
        $delRows = 1;
        if (strlen($keyValue) == 0)
        {
            $stmt2 = ExecuteQuery($conn1, "DELETE TOP(1) FROM [$tableName]");
            $cond = "(top row)";
        }
        else
        {
            $cond = "(c1_int = $keyValue)";

            $stmt3 = SelectFromTableEx($conn1, $tableName, $cond);
            $delRows = RowCount($stmt3);
            sqlsrv_free_stmt($stmt3);

            $stmt2 = ExecuteQuery($conn1, "DELETE FROM [$tableName] WHERE $cond");
        }
        $numRows1 = sqlsrv_rows_affected($stmt2);
        sqlsrv_free_stmt($stmt2);
        Trace(" $numRows1 row".(($numRows1 > 1) ? "s" : " ")." $cond.\n");

        if ($numRows1 != $delRows)
        {
            die("Unexpected row count at delete: $numRows1 instead of $delRows");   
        }
        $row += $numRows1;
    }

    $stmt3 = ExecuteQuery($conn1, "DELETE TOP(1) FROM [$tableName]");
    $numRows2 = sqlsrv_rows_affected($stmt3);
    sqlsrv_free_stmt($stmt3);

    if ($numRows2 > 0)
    {
        die("Unexpected row count at delete: $numRows2");
    }   
    
    DropTable($conn1, $tableName);  
    
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
        DeleteQuery();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Statement - Delete Query" completed successfully.