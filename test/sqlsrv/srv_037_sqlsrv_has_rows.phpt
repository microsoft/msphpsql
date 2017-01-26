--TEST--
sqlsrv_has_rows() using a forward and scrollable cursor
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect( $serverName, $connectionInfo);
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table
$query = "CREATE TABLE $tableName (ID VARCHAR(10))";
$stmt = sqlsrv_query($conn, $query);

$query = "INSERT INTO $tableName VALUES ('1998.1'),('-2004'),('2016'),('4.2EUR')";
$stmt = sqlsrv_query($conn, $query) ?: die( print_r( sqlsrv_errors(), true) );

// Fetch data using forward only cursor
$query = "SELECT ID FROM $tableName";
$stmt = sqlsrv_query($conn, $query)
		?: die( print_r(sqlsrv_errors(), true));
     
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

if (sqlsrv_has_rows($stmt)) {
	while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC))  
	{  
		echo $row[0]."\n";
	}
}

// Fetch data using a scrollable cursor
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"))
		?: die( print_r(sqlsrv_errors(), true));

// if we skip the next four calls it's fine
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

if (sqlsrv_has_rows($stmt)) {
	while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC))  
	{  
		echo $row[0]."\n";
	}
}

$query = "SELECT ID FROM $tableName where ID='nomatch'";
$stmt = sqlsrv_query($conn, $query)
		?: die( print_r(sqlsrv_errors(), true));
        
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

// Fetch data using a scrollable cursor
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"))
		?: die( print_r(sqlsrv_errors(), true));

echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";


// DROP database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
1998.1
-2004
2016
4.2EUR
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
1998.1
-2004
2016
4.2EUR
Has Rows? NO!
Has Rows? NO!
Has Rows? NO!
Has Rows? NO!
Done
