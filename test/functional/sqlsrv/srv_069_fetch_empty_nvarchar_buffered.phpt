--TEST--
GitHub issue #69 - fetching an empty nvarchar using client buffer
--SKIPIF--
--FILE--
<?php
function print_errors()
{
    die( print_r( sqlsrv_errors(), true));
}

function test()
{
    require_once("MsCommon.inc");

    // Connect
    $conn = Connect();
    if( !$conn ) { print_errors(); }
        
    $sql = "EXEC dbo.sp_executesql 
    N'DECLARE @x nvarchar(max)
    SET @x = '''' -- empty string
    SELECT @x AS [Empty_Nvarchar_Max]'";
    
    $stmt = sqlsrv_query($conn, $sql, [], ["Scrollable" => 'buffered']);
    if (! $stmt) { print_errors(); }

    $return = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); 
    print_r($return);
   
    // Free the statement and connection resources. 
    sqlsrv_free_stmt( $stmt);  
    sqlsrv_close( $conn);    
}
    
test();

print "Done";    
?> 
--EXPECT--
Array
(
    [Empty_Nvarchar_Max] => 
)
Done