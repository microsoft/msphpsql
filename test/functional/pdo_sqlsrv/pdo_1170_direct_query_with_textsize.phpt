--TEST--
GitHub issue 1170 - PDO::SQLSRV_ATTR_DIRECT_QUERY with SET TEXTSIZE
--DESCRIPTION--
This test verifies that setting PDO::SQLSRV_ATTR_DIRECT_QUERY to true with a user defined TEXTSIZE will work
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

function composeQuery($input, $type) 
{
    $sql = "
    SET NOCOUNT ON;
    DECLARE @T1 TABLE (C1 NVARCHAR(10), C2 $type)
    INSERT INTO @T1 (C1,C2) VALUES ('$input', NULL)
    SELECT * FROM @T1
    ";
    
    return $sql;
}

function runTest($conn, $type, $size)
{
    echo "Test with $type and $size\n";
    
    $input = 'TEST1';
    $options = array(PDO::SQLSRV_ATTR_DIRECT_QUERY => true);

    $sql = "SET TEXTSIZE $size";
    $stmt = $conn->prepare($sql, $options);
    $stmt->execute();
    unset($stmt);

    $sql = composeQuery($input, $type);
    $stmt = $conn->prepare($sql, $options);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['C1'] != $input || !is_null($row['C2'])) {
        var_dump($row);
    }
    unset($stmt);
}

try {  
    $conn = connect();

    $options = array(PDO::SQLSRV_ATTR_DIRECT_QUERY => true);

    runTest($conn, 'TEXT', 4800);
    runTest($conn, 'NTEXT', 129024);
    runTest($conn, 'IMAGE', 10000);

    unset($conn);

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
Test with TEXT and 4800
Test with NTEXT and 129024
Test with IMAGE and 10000
Done

