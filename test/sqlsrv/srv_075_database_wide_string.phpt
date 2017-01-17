--TEST--
PHPT Drop missing database
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

$connectionInfo = array( "UID"=>"$username", "PWD"=>"$password", "CharacterSet" => 'UTF-8');
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// Check if connected
if( !$conn ) {
     echo "Connection could not be established.\n";
     die( print_r( sqlsrv_errors(), true));
}

// Set database name
$dbUniqueName = "uniqueDB01_银河系";

// DROP database if exists
$stmt = sqlsrv_query($conn,"IF EXISTS(SELECT name FROM sys.databases WHERE name = '"
	.$dbUniqueName."') DROP DATABASE ".$dbUniqueName);
sqlsrv_free_stmt($stmt);

// DROP missing database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbUniqueName);
var_dump($stmt);
if( $stmt === false )
{
	$res = array_values(sqlsrv_errors());
	var_dump($res[0]['SQLSTATE']);
	var_dump($res[0][1]);
	var_dump($res[0][2]);

}
else {
    printf("%-20s\n","ERROR: DROP missing database MUST return bool(false)");
}

sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
bool(false)
string(5) "42S02"
int(3701)
string(159) "[Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Cannot drop the database 'uniqueDB01_银河系', because it does not exist or you do not have permission."
Done