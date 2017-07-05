--TEST--
Test unsupported connection attribute ATTR_PREFETCH
--DESCRIPTION--
Exception is thrown for the unsupported connection attribute ATTR_PREFETCH only if it is set after PDO::ERRMODE_EXCEPTION is turned on
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
$dsn = "sqlsrv:Server = $server;database = $databaseName";
try{
    echo "Testing a connection with ATTR_PREFETCH before ERRMODE_EXCEPTION...\n";
    $attr = array(PDO::ATTR_PREFETCH => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION); 
    $conn = new PDO( $dsn, $uid, $pwd, $attr); 
    echo "Error from supported attribute (ATTR_PREFETCH) is silented\n\n";
    $conn=null;
   
    echo "Testing a connection with ATTR_PREFETCH after ERRMODE_EXCEPTION...\n";
    $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PREFETCH => true); 
    $conn = new PDO( $dsn, $uid, $pwd, $attr); 
    //free the connection 
    $conn=null;
}
catch( PDOException $e ) {
    echo "Exception from unsupported attribute (ATTR_PREFETCH) is caught\n";
    //exit;
}
?> 
--EXPECT--
Testing a connection with ATTR_PREFETCH before ERRMODE_EXCEPTION...
Error from supported attribute (ATTR_PREFETCH) is silented

Testing a connection with ATTR_PREFETCH after ERRMODE_EXCEPTION...
Exception from unsupported attribute (ATTR_PREFETCH) is caught
