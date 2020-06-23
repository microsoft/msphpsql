--TEST--
GitHub issue #699 - binding integer as output parameter failed 
--DESCRIPTION--
This test uses the sample stored procedure provided by the user, in which an 
error situation caused the binding to fail with an irrelevant error message about UTF-8 translation. This test proves that this issue has been fixed.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$connectionOptions = array("CharacterSet"=> "UTF-8", "ConnectionPooling"=>1);
$conn = connect($connectionOptions);

$tableName1 = "table_issue699_1";
$tableName2 = "table_issue699_2";
$procName = "proc_issue699";

dropTable($conn, $tableName1);
dropTable($conn, $tableName2);
dropProc($conn, $procName);

// Create two test tables without encryption
$sql = "CREATE TABLE $tableName1 (correio_electronico NVARCHAR(50), nome NVARCHAR(50), telefones NVARCHAR(15), id_entidade INT)"; 
$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
    fatalError("Failed to create table $tableName1\n");
}

$sql = "CREATE TABLE $tableName2 (estado TINYINT NOT NULL DEFAULT 0)";
$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
    fatalError("Failed to create table $tableName2\n");
}

// Create the stored procedure
$sql = "CREATE PROCEDURE $procName @outparam INT OUTPUT AS 
        BEGIN 
            SET @outparam = 100; 
            INSERT INTO $tableName1 (correio_electronico, nome, telefones, id_entidade) 
            SELECT 'membros@membros.pt', 'Teste', 'xxx', 1 
            FROM $tableName2 CC 
            WHERE CC.estado = 100
            BEGIN TRY 
                SET @outparam = 123
            END TRY 
            BEGIN CATCH 
            END CATCH 
        END"; 

$stmt = sqlsrv_query($conn, $sql); 
if (!$stmt) { 
    fatalError("Error in creating the stored procedure $procName\n"); 
} 

$set_no_count = "";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
    // This test, when running outside of Windows, requires unixODBC 2.3.4 
    // or above (see the list of bug fixes in www.unixodbc.org)
    // Add this workaround for Linux platforms
    $set_no_count = "SET NOCOUNT ON; ";
}

$sql_callSP = $set_no_count . "{call $procName(?)}";

// Initialize the output parameter to any positive number
$outParam = 1; 
$params = array(array(&$outParam, SQLSRV_PARAM_OUT));
$stmt = sqlsrv_query($conn, $sql_callSP, $params); 
if (!$stmt) { 
    fatalError("Error in calling $procName\n"); 
} 

while ($res = sqlsrv_next_result($stmt)); 

if ($outParam != 123) {
    echo "The output param value $outParam is unexpected!\n";
} 

// Initialize the output parameter to any negative number
$outParam = -1; 
$params = array(array(&$outParam, SQLSRV_PARAM_OUT));
$stmt = sqlsrv_prepare($conn, $sql_callSP, $params);
if (!$stmt) { 
    fatalError("Error in preparing $procName\n"); 
} 
$res = sqlsrv_execute($stmt);
if (!$res) { 
    fatalError("Error in executing $procName\n");
} 

while ($res = sqlsrv_next_result($stmt)); 

if ($outParam != 123) {
    echo "The output param value $outParam is unexpected!\n";
} 

dropTable($conn, $tableName1);
dropTable($conn, $tableName2);
dropProc($conn, $procName);

// Free handles
sqlsrv_free_stmt($stmt); 
sqlsrv_close($conn); 

echo "Done\n";

?>
--EXPECT--
Done