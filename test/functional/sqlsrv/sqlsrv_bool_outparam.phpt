--TEST--
Test for binding boolean output and inout parameters
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

// Create the table
$tbname = "bool_table";
AE\createTable($conn, $tbname, array(new AE\ColumnMeta("int", "c1_bool")));

// Create a Stored Procedure with output
$spname = "selectBool";
dropProc($conn, $spname);

$spSql = "CREATE PROCEDURE $spname (@c1_bool int OUTPUT) AS
          SELECT @c1_bool = c1_bool FROM $tbname";
sqlsrv_query($conn, $spSql);

// Insert 1
AE\insertRow($conn, $tbname, array("c1_bool" => 1));

// Call stored procedure with SQLSRV_PARAM_OUT
$outSql = "{CALL $spname (?)}";
$boolOut = false;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$boolOut, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)));
sqlsrv_execute($stmt);
printf("True bool output:\n");
var_dump($boolOut);
printf("\n");

// Call stored procedure with SQLSRV_PARAM_INOUT
$boolOut = false;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$boolOut, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT)));
sqlsrv_execute($stmt);
printf("True bool inout:\n");
var_dump($boolOut);
printf("\n");
sqlsrv_query($conn, "TRUNCATE TABLE $tbname");

// Insert 0
AE\insertRow($conn, $tbname, array("c1_bool" => 0));

// Call stored procedure with SQLSRV_PARAM_OUT
$boolOut = true;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$boolOut, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)));
sqlsrv_execute($stmt);
printf("False bool output:\n");
var_dump($boolOut);
printf("\n");

// Call stored procedure with SQLSRV_PARAM_INOUT
$boolOut = true;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$boolOut, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT)));
sqlsrv_execute($stmt);
printf("False bool inout:\n");
var_dump($boolOut);
printf("\n");

dropProc($conn, $spname);
dropTable($conn, $tbname);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
True bool output:
bool(true)

True bool inout:
bool(true)

False bool output:
bool(false)

False bool inout:
bool(false)
