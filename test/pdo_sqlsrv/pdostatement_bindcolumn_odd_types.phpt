--TEST--
Test the bindColumn method using PDO::PARAM_NULL and PDO::PARAM_STMT
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

try
{
	$conn = new PDO( "sqlsrv:Server=$serverName; Database = tempdb ", $username, $password);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $conn->exec("IF OBJECT_ID('temptb', 'U') IS NOT NULL DROP TABLE temptb");
    $conn->exec("CREATE TABLE temptb(IntCol INT, CharCol NVARCHAR(20)) ");
    $conn->exec("INSERT INTO temptb (IntCol, CharCol) VALUES (10, 'ten')");
    
    $stmt = $conn->prepare("SELECT IntCol FROM temptb");
	$stmt->execute();
    
    // PARAM_NULL returns null
	$stmt->bindColumn('IntCol', $intCol, PDO::PARAM_NULL);
    $row = $stmt->fetch(PDO::FETCH_BOUND);
    if ($intCol == NULL) {
        echo "intCol is NULL\n";
    } else {
        echo "intCol should have been NULL\n";
    }
    
    $stmt = $conn->prepare("SELECT CharCol FROM temptb");
	$stmt->execute();
    
    // PARAM_STMT is not support and should throw an exception
	$stmt->bindColumn('CharCol', $charCol, PDO::PARAM_STMT);
    $row = $stmt->fetch(PDO::FETCH_BOUND);
    echo "PARAM_STMT should have thrown an exception\n";
    
}
catch (PDOException $e)
{
	print_r($e->errorInfo[2]);
    echo "\n";
}

?>
--EXPECT--
intCol is NULL
PDO::PARAM_STMT is not a supported parameter type.