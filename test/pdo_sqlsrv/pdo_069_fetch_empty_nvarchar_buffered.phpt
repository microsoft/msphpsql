--TEST--
GitHub issue #69 - fetching an empty nvarchar using client buffer
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
// Connect 
require_once("MsSetup.inc");

$conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd);   
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );   
    
$sql = "EXEC dbo.sp_executesql 
N'DECLARE @x nvarchar(max)
SET @x = '''' -- empty string
SELECT @x AS [Empty_Nvarchar_Max]'";

$stmt = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));  
$stmt->execute();

$return = $stmt->fetchAll( PDO::FETCH_ASSOC ); 
print_r($return);

// Free the statement and connection resources. 
$stmt = null;  
$conn = null;    

print "Done";
?> 
--EXPECT--
Array
(
    [0] => Array
        (
            [Empty_Nvarchar_Max] => 
        )

)
Done