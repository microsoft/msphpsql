--TEST--
sqlsrv_get_field() using SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR)
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect( $serverName, $connectionInfo);
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create table
$query = "CREATE TABLE #TA1 (ID NVARCHAR(10))";
$stmt = sqlsrv_query($conn, $query);

// Insert data
$query = "INSERT INTO #TA1 VALUES ('1998.1'),('-2004.2436'),('4.2 EUR')";
$stmt = sqlsrv_query($conn, $query) ?: die( print_r( sqlsrv_errors(), true) );

// Fetch data
$query = "SELECT * FROM #TA1";
$stmt = sqlsrv_query( $conn, $query ) ?: die( print_r( sqlsrv_errors(), true) );

while(sqlsrv_fetch($stmt)) {
	$field = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
	var_dump($field);
	
	while(!feof($field))
	{
		echo fread($field, 100)."\n";
	}
}

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
resource(10) of type (stream)
1998.1
resource(11) of type (stream)
-2004.2436
resource(12) of type (stream)
4.2 EUR
Done
