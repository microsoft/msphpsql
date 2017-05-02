--TEST--
Test getting invalid attributes
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once("MsSetup.inc");

try{
    $databaseName = "tempdb";
    $dsn = 	"sqlsrv:Server = $server; database = $databaseName";

    $conn = new PDO( $dsn, $uid, $pwd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT)); 
   
    @$conn->getAttribute( PDO::ATTR_FETCH_TABLE_NAMES );
    print_r (($conn->errorInfo())[2]);
    echo "\n";

    @$conn->getAttribute( PDO::ATTR_CURSOR );
    print_r (($conn->errorInfo())[2]);
    echo "\n";

    @$conn->getAttribute( PDO::ATTR_CONNECTION_STATUS );
    print_r (($conn->errorInfo())[2]);
    echo "\n";
}
catch ( PDOException $e ){
    echo $e->getMessage();
}

?> 

--EXPECT--

An unsupported attribute was designated on the PDO object.
The given attribute is only supported on the PDOStatement object.
An invalid attribute was designated on the PDO object.