--TEST--
Test for binding bigint output and inout parameters
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once("MsHelper.inc");

$conn = AE\connect();

// Create the table
$tbname = "bigint_table";
AE\createTable($conn, $tbname, array(new AE\ColumnMeta("bigint", "c1_bigint")));


// Create a Stored Procedure with output
$spname = "selectBigint";
$spSql = "CREATE PROCEDURE $spname (@c1_bigint bigint OUTPUT) AS
          SELECT @c1_bigint = c1_bigint FROM $tbname";
sqlsrv_query( $conn, $spSql );

// Insert a large bigint
AE\insertRow($conn, $tbname, array("c1_bigint" => 922337203685479936));

// Call stored procedure with SQLSRV_PARAM_OUT
$outSql = "{CALL $spname (?)}";
$bigintOut = 0;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$bigintOut, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_BIGINT)));
sqlsrv_execute($stmt);
printf("Large bigint output:\n");
var_dump($bigintOut);
printf("\n");

// Call stored procedure with SQLSRV_PARAM_INOUT
$bigintOut = 0;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$bigintOut, SQLSRV_PARAM_INOUT, null, SQLSRV_SQLTYPE_BIGINT)));
sqlsrv_execute($stmt);
printf("Large bigint inout:\n");
var_dump($bigintOut);
printf("\n");
sqlsrv_query($conn, "TRUNCATE TABLE $tbname");

// Insert a random small value truncated from the bigint input
AE\insertRow($conn, $tbname, array("c1_bigint" => 922337203));

// Call stored procedure with SQLSRV_PARAM_OUT
$bigintOut = 0;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$bigintOut, SQLSRV_PARAM_OUT)));
sqlsrv_execute($stmt);
printf("Small bigint output:\n");
var_dump($bigintOut);
printf("\n");

// Call stored procedure with SQLSRV_PARAM_INOUT
$bigintOut = 0;
$stmt = sqlsrv_prepare($conn, $outSql, array(array(&$bigintOut, SQLSRV_PARAM_INOUT)));
sqlsrv_execute($stmt);
printf("Small bigint inout:\n");
var_dump($bigintOut);
printf("\n");

dropProc($conn, $spname);
dropTable($conn, $tbname);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

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
