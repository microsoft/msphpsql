--TEST--
GitHub issue #69 - fetching an empty nvarchar using client buffer
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
// Connect 
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
        
    $sql = "EXEC dbo.sp_executesql 
    N'DECLARE @x nvarchar(max)
    SET @x = '''' -- empty string
    SELECT @x AS [Empty_Nvarchar_Max]'";

    $stmt = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));  
    $stmt->execute();

    $return = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    print_r($return);

    // Free the statement and connection resources. 
    unset($stmt);
    unset($conn);  

    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
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