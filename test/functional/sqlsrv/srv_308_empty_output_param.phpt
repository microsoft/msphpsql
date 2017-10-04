--TEST--
GitHub issue #308 - empty string set to output parameter on stored procedure
--DESCRIPTION--
A variation of the example in GitHub issue 308. A NULL value returned as output parameter will remain as NULL.
--SKIPIF--
--FILE--
<?php
require_once("MsCommon.inc");

// connect
$conn = connect() ?: fatalError("Failed to connect");

$procName = GetTempProcName();

$sql = "CREATE PROCEDURE $procName @TEST VARCHAR(200)='' OUTPUT
AS BEGIN
SET NOCOUNT ON;
SET @TEST=NULL;
SELECT HELLO_WORLD_COLUMN='THIS IS A COLUMN IN A SINGLE DATASET';
END";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    fatalError("Failed to create stored procedure");
}

$sql = "EXEC $procName @Test = ?";
$out = '';

$param = array(array(&$out, SQLSRV_PARAM_INOUT));
$stmt = sqlsrv_query($conn, $sql, $param);
if ($stmt === false) {
    fatalError("Failed to execute stored procedure");
}

$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
sqlsrv_next_result($stmt);

echo "OUT value: ";
var_dump($out);

// Free statement and connection resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done";
?>
--EXPECT--
OUT value: NULL
Done
