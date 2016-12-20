--TEST--
Connect with options
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

$connectionInfo = array("Database"=>"master", "UID"=>"$username", "PWD"=>"$password");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

if( !$conn ) {
     echo "Connection could not be established.\n";
     die( print_r( sqlsrv_errors(), true));
}
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
Done
