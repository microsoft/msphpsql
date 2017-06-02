--TEST--
Insert binary HEX data then fetch it back as string
--DESCRIPTION--
Insert binary HEX data into an nvarchar field then read it back as UTF-8 string using sqlsrv_get_field()
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

// Connect
$conn = Connect();
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create table
$tableName = '#srv_033test';
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (c1 NVARCHAR(100))");

$input = pack( "H*", '49006427500048005000' );  // I'LOVE_SYMBOL'PHP


$s = sqlsrv_query( $conn, "INSERT INTO $tableName (c1) VALUES (?)",
                   array(array( $input, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)) 
                       ));
if( $s === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$s = sqlsrv_query( $conn, "SELECT * FROM $tableName" );
if( $s === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

sqlsrv_fetch( $s );

$utf8 = sqlsrv_get_field( $s, 0, SQLSRV_PHPTYPE_STRING('utf-8') );

echo "\n". $utf8 ."\n";

print_r( sqlsrv_errors() );
sqlsrv_close( $conn );

print "Done";

?>

--EXPECT--
I❤PHP
Done
