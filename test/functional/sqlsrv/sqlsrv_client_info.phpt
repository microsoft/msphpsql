--TEST--
Test sqlsrv_client_info
--SKIPIF--
--FILE--
<?php
require_once("MsCommon.inc");
$conn = Connect();
if( !$conn ) { 
    die( print_r( sqlsrv_errors(), true)); 
}

$client_info = sqlsrv_client_info( $conn );
var_dump( $client_info );
?>
--EXPECTREGEX--
array\(4\) {
  \[\"(DriverDllName|DriverName)\"\]=>
  (string\([0-9]+\) \"msodbcsql1[1-9].dll\"|string\([0-9]+\) \"(libmsodbcsql-[0-9]{2}\.[0-9]\.so\.[0-9]\.[0-9]|libmsodbcsql.[0-9]{2}.dylib)\")
  \[\"DriverODBCVer\"\]=>
  string\(5\) \"[0-9]{1,2}\.[0-9]{1,2}\"
  \[\"DriverVer\"\]=>
  string\(10\) \"[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}\"
  \[\"ExtensionVer\"\]=>
  string\([0-9]+\) \"[0-9].[0-9]\.[0-9](-(RC[0-9]?|preview))?(\.[0-9]+)?(\+[0-9]+)?\"
}