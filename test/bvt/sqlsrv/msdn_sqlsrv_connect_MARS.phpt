--TEST--
disables MARS support.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);

/* Connect to the local server using Windows Authentication and
specify the AdventureWorks database as the database in use. */
$serverName = "sql-2k14-sp1-1.galaxy.ad";
$connectionInfo = array( "Database"=>"AdventureWorks2014", "UID"=>"sa", "PWD"=>"Moonshine4me", 'MultipleActiveResultSets'=> false);
$conn = sqlsrv_connect( $serverName, $connectionInfo);
if( $conn === false )
{
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