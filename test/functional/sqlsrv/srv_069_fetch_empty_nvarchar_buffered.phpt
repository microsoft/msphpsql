--TEST--
GitHub issue #69 - fetching an empty nvarchar using client buffer
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

// Connect
$conn = AE\connect();
    
$sql = "EXEC dbo.sp_executesql 
N'DECLARE @x nvarchar(max)
SET @x = '''' -- empty string
SELECT @x AS [Empty_Nvarchar_Max]'";

$stmt = AE\executeQueryEx($conn, $sql, ["Scrollable" => 'buffered']);

$return = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); 
print_r($return);

// Free the statement and connection resources. 
sqlsrv_free_stmt( $stmt);  
sqlsrv_close( $conn);    

print "Done";    
?> 
--EXPECT--
Array
(
    [Empty_Nvarchar_Max] => 
)
Done