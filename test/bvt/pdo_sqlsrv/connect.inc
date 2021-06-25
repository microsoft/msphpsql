<?php
$server = 'TARGET_SERVER';
$databaseName = 'TARGET_DATABASE';
$uid = 'TARGET_USERNAME';
$pwd = 'TARGET_PASSWORD';

function dropTable($conn, $tableName)
{
    $tsql = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE [$tableName]";
    $conn->exec($tsql);
}

function dropProc($conn, $procName)
{
    $tsql = "IF OBJECT_ID('". $procName ."', 'P') IS NOT NULL DROP PROCEDURE [$procName]";
    $conn->exec($tsql);
}

?>


