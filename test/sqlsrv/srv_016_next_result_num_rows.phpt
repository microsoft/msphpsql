--TEST--
Scrollable buffered result set: sqlsrv_next_result(), sqlsrv_num_rows()
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect($serverName, $connectionInfo);
if( !$conn ) { die(print_r( sqlsrv_errors(), true)); }

// Query system databases
$query = "
	select top (8) name, is_read_only FROM sys.databases;
	select top (11) state_desc from sys.databases; 
	select top (2) user_access_desc from sys.databases;";

$params = array();
$options = array("Scrollable"=>"buffered");
$stmt = sqlsrv_query($conn, $query, $params, $options);
$row_count[] = sqlsrv_num_rows($stmt);

sqlsrv_next_result($stmt);
$row_count[] = sqlsrv_num_rows($stmt);

sqlsrv_next_result($stmt);
$row_count[] = sqlsrv_num_rows($stmt);

print_r($row_count);

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
echo "Done";
?>

--EXPECT--
Array
(
    [0] => 8
    [1] => 11
    [2] => 2
)
Done

