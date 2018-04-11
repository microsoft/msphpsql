--TEST--
Test for binding bigint output and inout parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$conn = connect();

// Create the table
$tbname = "bigint_table";
createTable($conn, $tbname, array("c1_bigint" => "bigint"));

// Create a Stored Procedure
$spname = "selectBigint";
dropProc($conn, $spname);
$spSql = "CREATE PROCEDURE $spname (@c1_bigint bigint OUTPUT) AS
          SELECT @c1_bigint = c1_bigint FROM $tbname";
$conn->query($spSql);

// Insert a large bigint
insertRow($conn, $tbname, array("c1_bigint" => 922337203685479936));

// Call stored procedure with output
$outSql = "{CALL $spname (?)}";
$bigintOut = 0;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $bigintOut, PDO::PARAM_STR, 32);
$stmt->execute();
printf("Large bigint output:\n" );
var_dump($bigintOut);
printf("\n");

// Call stored procedure with inout
$bigintOut = 0;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $bigintOut, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2048);
$stmt->execute();
printf("Large bigint inout:\n" );
var_dump($bigintOut);
printf("\n");

$conn->exec("TRUNCATE TABLE $tbname");

// Insert a random small value truncated from the bigint input
insertRow($conn, $tbname, array("c1_bigint" => 922337203));

// Call stored procedure with output
$bigintOut = 0;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $bigintOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->execute();
printf("Small bigint output:\n" );
var_dump($bigintOut);
printf("\n");

// Call stored procedure with inout
$bigintOut = 0;
$stmt = $conn->prepare($outSql);
$stmt->bindParam(1, $bigintOut, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->execute();
printf("Small bigint inout:\n" );
var_dump($bigintOut);
printf("\n");

dropProc($conn, $spname);
dropTable($conn, $tbname);
unset($stmt);
unset($conn);
?>
--EXPECT--
Large bigint output:
string(18) "922337203685479936"

Large bigint inout:
string(18) "922337203685479936"

Small bigint output:
int(922337203)

Small bigint inout:
int(922337203)
