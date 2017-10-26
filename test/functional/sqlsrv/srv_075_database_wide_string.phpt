--TEST--
Drop missing database unicode
--SKIPIF--
<?php require('skipif_azure.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

$connectionInfo = array("CharacterSet"=>"UTF-8");
$conn = connect($connectionInfo);
if (!$conn) {
    printErrors("Could not connect.\n");
}

// Set database name
$dbUniqueName = "uniqueDB01_银河系";

// DROP database if exists
$stmt = sqlsrv_query($conn, "IF EXISTS(SELECT name FROM sys.databases WHERE name = '"
    .$dbUniqueName."') DROP DATABASE ".$dbUniqueName);
sqlsrv_free_stmt($stmt);

// DROP missing database
$stmt = sqlsrv_query($conn, "DROP DATABASE ". $dbUniqueName);
var_dump($stmt);
if ($stmt === false) {
    $res = array_values(sqlsrv_errors());
    var_dump($res[0]['SQLSTATE']);
    var_dump($res[0][1]);
    var_dump($res[0][2]);
} else {
    printf("%-20s\n", "ERROR: DROP missing database MUST return bool(false)");
}

sqlsrv_close($conn);
print "Done";
?>

--EXPECTREGEX--
bool\(false\)
string\(5\) "(42S02|08004)"
int\((3701|911)\)
string\([0-9]+\) "\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\](Cannot drop the database 'uniqueDB01_银河系', because it does not exist or you do not have permission\.|Database 'uniqueDB01_银河系' does not exist. Make sure that the name is entered correctly\.)"
Done
