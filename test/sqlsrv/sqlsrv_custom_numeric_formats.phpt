--TEST--
Custom numeric formats for the SQL FORMAT function 
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// Connect
$conn = Connect();
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create table
$tableName = '#testNumericFormats';
$query = "CREATE TABLE $tableName (Col INT, Total MONEY, Percentage DECIMAL(5,2))";
$stmt = sqlsrv_query($conn, $query);

// Insert data
$query = "INSERT INTO $tableName VALUES ('2', 56.05, 20),('0', 79.99, 0.5),('199', 5, 0),('2147483646', 7.99, 48)";
$stmt = sqlsrv_query($conn, $query) ?: die(print_r( sqlsrv_errors(), true));

// Fetch data
$query = "SELECT FORMAT(Col,'#,##0.00 CAD;;Free') FROM $tableName";
$stmt = sqlsrv_query($conn, $query)
        ?: die( print_r(sqlsrv_errors(), true));

// Fetch
while($row = sqlsrv_fetch_array($stmt))
    echo $row[0]."\n";
   
$query = "SELECT FORMAT(Total, 'C'), FORMAT(Percentage, 'N') FROM $tableName";
$stmt = sqlsrv_query($conn, $query)
        ?: die( print_r(sqlsrv_errors(), true));

// Fetch
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC))
    print_r($row);

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
2.00 CAD
Free
199.00 CAD
2,147,483,646.00 CAD
Array
(
    [0] => $56.05
    [1] => 20.00
)
Array
(
    [0] => $79.99
    [1] => 0.50
)
Array
(
    [0] => $5.00
    [1] => 0.00
)
Array
(
    [0] => $7.99
    [1] => 48.00
)
Done
