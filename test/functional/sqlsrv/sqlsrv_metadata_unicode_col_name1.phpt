--TEST--
PHP - Retrieve Unicode column name using sqlsrv_fetch_metadata
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
$tableName = "UnicodeColNameTest";
$columnName = "此是後話Κοντάוְאַתָּה第十四章BiałopioБунтевсемужирафиtest是أي بزمام الإنذارהნომინავიiałopioБунтевсемужирафиtest父親回衙 汗流如雨 吉安而來. 關雎 誨€¥É§é";

include 'MsSetup.inc';
$conn = ConnectUTF8();

DropTable($conn, $tableName);
$stmt  = sqlsrv_query($conn, "CREATE TABLE [$tableName] ([$columnName] varchar(5))");
$stmt = sqlsrv_query($conn, "SELECT * from [$tableName]");
$meta = sqlsrv_field_metadata( $stmt );
echo $meta[0]["Name"];

DropTable($conn, $tableName);
sqlsrv_free_stmt( $stmt);
sqlsrv_close($conn);
?>
--EXPECT--
此是後話Κοντάוְאַתָּה第十四章BiałopioБунтевсемужирафиtest是أي بزمام الإنذارהნომინავიiałopioБунтевсемужирафиtest父親回衙 汗流如雨 吉安而來. 關雎 誨€¥É§é