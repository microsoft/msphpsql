--TEST--
Create/drop database 
--SKIPIF--
<?php require('skipif_azure.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

$conn = ConnectSpecial(array("Database"=>'master'));
if( !$conn ) {
    FatalError("Connection could not be established.\n");
}

// Set database name
$dbUniqueName = "php_uniqueDB01";

// DROP database if exists
$stmt = sqlsrv_query($conn,"IF EXISTS(SELECT name FROM sys.databases WHERE name = '"
    .$dbUniqueName."') DROP DATABASE ".$dbUniqueName);
if($stmt === false){ die( print_r( sqlsrv_errors(), true )); }
sqlsrv_free_stmt($stmt);

// CREATE database
$stmt = sqlsrv_query($conn,"CREATE DATABASE ". $dbUniqueName);
if($stmt === false){ die( print_r( sqlsrv_errors(), true )); }
echo "DATABASE CREATED\n";

// DROP database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbUniqueName);
if($stmt === false){ die( print_r( sqlsrv_errors(), true )); }
echo "DATABASE DROPPED\n";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
DATABASE CREATED
DATABASE DROPPED
Done
