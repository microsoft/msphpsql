--TEST--
Test for binding boolean output and inout parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$conn = connect();

// Create the table
$tbname = "bool_table";
createTable($conn, $tbname, array("c1_bool" => "int"));

// Create a Stored Procedure
$spname = "selectBool";
dropProc($conn, $spname);
$spSql = "CREATE PROCEDURE $spname (@c1_bool int OUTPUT) AS
          SELECT @c1_bool = c1_bool FROM $tbname";
$conn->query($spSql);

// Insert 1
insertRow($conn, $tbname, array("c1_bool" => 1));

// Call stored procedure with output
$outSql = "{CALL $spname (?)}";
$boolOut = false;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $boolOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->execute();
printf("True bool output:\n" );
var_dump($boolOut);
printf("\n");

// Call stored procedure with inout
$boolOut = false;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $boolOut, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->execute();
printf("True bool inout:\n" );
var_dump($boolOut);
printf("\n");

$conn->exec("TRUNCATE TABLE $tbname");

// Insert 0
insertRow($conn, $tbname, array("c1_bool" => 0));

// Call stored procedure with output
$boolOut = true;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $boolOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->execute();
printf("True bool output:\n" );
var_dump($boolOut);
printf("\n");

// Call stored procedure with inout
$boolOut = true;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $boolOut, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->execute();
printf("True bool inout:\n" );
var_dump($boolOut);
printf("\n");

dropProc($conn, $spname);
dropTable($conn, $tbname);
unset($stmt);
unset($conn);
?>
--EXPECT--
True bool output:
int(1)

True bool inout:
int(1)

True bool output:
int(0)

True bool inout:
int(0)
