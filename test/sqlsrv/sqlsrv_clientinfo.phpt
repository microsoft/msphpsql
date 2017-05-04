--TEST--
Verify sqlsrv_client_info
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php 
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
    sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_OFF );

    require( 'MsCommon.inc' );

    $conn = Connect();
    if( !$conn ) {
        FatalError("Could not connect");
    }

    $client_info = sqlsrv_client_info( $conn );
    var_dump( $client_info );
?>
--EXPECTREGEX--
array\(4\) {
  \[\"(DriverDllName|DriverName)\"\]=>
  (string\(15\) \"msodbcsql1[1-9].dll\"|string\(24\) \"libmsodbcsql-[1-9]{2}.0.so.1.0\")
  \[\"DriverODBCVer\"\]=>
  string\(5\) \"[0-9]{1,2}\.[0-9]{1,2}\"
  \[\"DriverVer\"\]=>
  string\(10\) \"[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}\"
  \[\"ExtensionVer\"\]=>
  string\([0-9]+\) \"[0-9]\.[0-9]\.[0-9](\-((rc)|(preview))(\.[0-9]+)?)?(\+[0-9]+)?\"
}
