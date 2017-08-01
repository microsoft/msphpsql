--TEST--
Connection option APP unicode
--DESCRIPTION--
Connect using a Unicode App name. Once connected, fetch APP_NAME.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once("MsCommon.inc");

// Connect
$appName = "APP_PoP_";
$appName = $appName . str_repeat("银河系",1);

$conn = Connect(array("APP"=>$appName, "CharacterSet"=>"utf-8"));
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Query and print out
$sql = "select APP_NAME()";
$stmt = sqlsrv_query($conn, $sql);
if( !$stmt ) { die( print_r( sqlsrv_errors(), true)); }

// Fetch the data
while( sqlsrv_fetch($stmt) ) {
    echo sqlsrv_get_field($stmt, 0)."\n";
}

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
APP_PoP_银河系
Done
