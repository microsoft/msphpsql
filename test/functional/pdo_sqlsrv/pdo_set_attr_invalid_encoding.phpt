--TEST--
Test setting invalid encoding attributes
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once("MsSetup.inc");

try{
    $dsn = 	"sqlsrv:Server = $server; database = $databaseName";

    $conn = new PDO( $dsn, $uid, $pwd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT)); 

    // valid option: should have no error
    @$conn->setAttribute( PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_DEFAULT );
    print_r (($conn->errorInfo())[2]);
    echo "\n";

    // PDO::SQLSRV_ENCODING_UTF8 should not be quoted
    @$conn->setAttribute( PDO::SQLSRV_ATTR_ENCODING, "PDO::SQLSRV_ENCODING_UTF8" );
    print_r (($conn->errorInfo())[2]);
    echo "\n";

    // PDO::SQLSRV_ENCODING_BINARY is not supported
    @$conn->setAttribute( PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_BINARY );
    print_r (($conn->errorInfo())[2]);
    echo "\n";
}
catch ( PDOException $e ){
    echo $e->getMessage();
}

?> 

--EXPECT--

An invalid encoding was specified for SQLSRV_ATTR_ENCODING.
An invalid encoding was specified for SQLSRV_ATTR_ENCODING.