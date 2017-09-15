--TEST--
Maximum length outputs from stored procs for string types (nvarchar, varchar, and varbinary)
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

set_time_limit(0);

$inValue1 = str_repeat( 'A', 3999 );

$outValue1 = "TEST";

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSubsystems', 15);

require( 'MsCommon.inc' );

$conn = Connect();

$field_type = 'NVARCHAR(4000)';

$stmt = sqlsrv_query($conn, "DROP PROC [TestFullLenStringsOut]");
$stmt = sqlsrv_query($conn, "CREATE PROC [TestFullLenStringsOut] (@p1 " . $field_type . ", @p2 " . $field_type . " OUTPUT)
 AS
 BEGIN
   SELECT @p2 = CONVERT(" . $field_type . ", @p1 + N'A')
 END");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

// remember to increment buffer_len to 8001 to see what happens 
$stmt = sqlsrv_query($conn, "{CALL [TestFullLenStringsOut] (?, ?)}", 
      array(
        array($inValue1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_NVARCHAR(4000)),
        array(&$outValue1, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_NVARCHAR(4000))));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
while( sqlsrv_next_result( $stmt )) {}
print_r( strlen( $outValue1 ));
echo "\n";
print_r( substr( $outValue1, -2, 2 ));

$field_type = 'VARCHAR(8000)';
$inValue1 = str_repeat( 'A', 7999 );

$stmt = sqlsrv_query($conn, "DROP PROC [TestFullLenStringsOut]");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "CREATE PROC [TestFullLenStringsOut] (@p1 " . $field_type . ", @p2 " . $field_type . " OUTPUT)
 AS
 BEGIN
   SELECT @p2 = CONVERT(" . $field_type . ", @p1 + 'A')
 END" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query($conn, "{CALL [TestFullLenStringsOut] (?, ?)}", 
     array(
       array($inValue1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(8000)),
       array(&$outValue1, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(8000))));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
while( sqlsrv_next_result( $stmt )) {}
echo "\n";
print_r( strlen( $outValue1 ));
echo "\n";
print_r( substr( $outValue1, -2, 2 ));

$field_type = 'VARBINARY(8000)';
$inValue1 = str_repeat( 'A', 7999 );

$stmt = sqlsrv_query($conn, "DROP PROC [TestFullLenStringsOut]");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query($conn, "CREATE PROC [TestFullLenStringsOut] (@p1 " . $field_type . ", @p2 " . $field_type . " OUTPUT)
 AS
 BEGIN
   SELECT @p2 = CONVERT(" . $field_type . ", @p1 + 0x42)
 END");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query($conn, "{CALL [TestFullLenStringsOut] (?, ?)}", 
     array(
       array($inValue1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(8000)),
       array(&$outValue1, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(8000))));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
while( sqlsrv_next_result( $stmt )) {}
echo "\n";
print_r( strlen( $outValue1 ));
echo "\n";
print_r( substr( $outValue1, -2, 2 ));

sqlsrv_close( $conn );

?>
--EXPECT--
4000
AA
8000
AA
8000
AB
