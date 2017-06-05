--TEST--
retrieve date as PHP type with ReturnDatesAsStrings off by default.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
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

$date_string = date_format( $date, 'jS, F Y' );
echo "Date = $date_string\n";

sqlsrv_close( $conn);
?>
--EXPECT--
Date = 20th, February 2014