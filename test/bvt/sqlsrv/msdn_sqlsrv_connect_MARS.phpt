--TEST--
disables MARS support.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$connectionInfo = array("Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd, 'MultipleActiveResultSets'=> false);
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false ) {
   echo "Could not connect.\n";
   die( print_r( sqlsrv_errors(), true));
}
else
{
	echo "Connection established.\n";
}

sqlsrv_close( $conn);
?>
--EXPECT--
Connection established.