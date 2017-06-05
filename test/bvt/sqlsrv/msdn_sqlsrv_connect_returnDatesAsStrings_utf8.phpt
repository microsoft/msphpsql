--TEST--
retrieves dates as string by specifying UTF-8 and ReturnDatesAsStrings when connecting.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd", 'ReturnDatesAsStrings'=> true, "CharacterSet" => 'utf-8');
$conn = sqlsrv_connect( $server, $connectionInfo);

if( $conn === false ) {
   echo "Could not connect.\n";
   die( print_r( sqlsrv_errors(), true));
}

$tsql = "SELECT VersionDate FROM AWBuildVersion";

$stmt = sqlsrv_query( $conn, $tsql);

if ( $stmt === false ) {
   echo "Error in statement preparation/execution.\n";
   die( print_r( sqlsrv_errors(), true));
}

sqlsrv_fetch( $stmt );

// retrieve date as string
$date = sqlsrv_get_field( $stmt, 0 );

if ( $date === false ) {
   die( print_r( sqlsrv_errors(), true ));
}

echo $date;
sqlsrv_close( $conn);
?>
--EXPECT--
2014-02-20 04:26:00.000