<?php
$server = 'TARGET_SERVER';
$databaseName = 'TARGET_DATABASE';
$uid = 'TARGET_USERNAME';
$pwd = 'TARGET_PASSWORD';

function dropTable($conn, $tableName)
{
    $tsql = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE [$tableName]";
    sqlsrv_query($conn, $tsql);
}

function dropProc($conn, $procName)
{
    $tsql = "IF OBJECT_ID('". $procName ."', 'P') IS NOT NULL DROP PROCEDURE [$procName]";
    sqlsrv_query($conn, $tsql);
}

// RevisionNumber in SalesOrderHeader is subject to a trigger incrementing it whenever
// changes are made to SalesOrderDetail. Since RevisionNumber is a tinyint, it can
// overflow quickly if the BVT tests often run. Disable the trigger.
function disableTrigger($conn)
{   
    sqlsrv_query($conn, 'DISABLE TRIGGER uSalesOrderHeader ON Sales.SalesOrderHeader');
}
?>
