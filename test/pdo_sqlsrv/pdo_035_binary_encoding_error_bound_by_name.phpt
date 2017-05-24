--TEST--
GitHub Issue #35 binary encoding error when binding by name
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
function test()
{
    require_once("MsSetup.inc");

    // Connect
    $conn = new PDO( "sqlsrv:server=$server ; database=$databaseName", $uid, $pwd);

    // Create a temp table
    $tableName = "#testTableIssue35";
    $sql = "CREATE TABLE $tableName (Value varbinary(max))";
    $stmt = $conn->exec($sql);

    // Insert data using bind parameters
    $sql = "INSERT INTO $tableName VALUES (?)";
    $stmt = $conn->prepare($sql);
    $message = "This is to test github issue 35.";
    $value = base64_encode($message);
    
    $stmt->setAttribute(constant('PDO::SQLSRV_ATTR_ENCODING'), PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(1, $value, PDO::PARAM_LOB); 
    $result = $stmt->execute();

    // fetch it back
    $stmt = $conn->prepare("SELECT Value FROM $tableName"); 
    $stmt->bindColumn('Value', $val1, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);  
    $stmt->execute();
    $stmt->fetch(PDO::FETCH_BOUND);  
    var_dump($val1 === $value);
        
    // Close connection
    $stmt = null;
    $conn = null;
}

test();
print "Done";
?>
--EXPECT--
bool(true)
Done

