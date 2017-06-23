--TEST--
Test the bindColumn method using PDO::PARAM_NULL and PDO::PARAM_STMT
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once("MsSetup.inc");

try
{
    $conn = new PDO( "sqlsrv:Server=$server; database = $databaseName ", $uid, $pwd);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $conn->exec("IF OBJECT_ID('table1', 'U') IS NOT NULL DROP TABLE table1");
    $conn->exec("CREATE TABLE table1(IntCol INT, CharCol NVARCHAR(20)) ");
    $conn->exec("INSERT INTO table1 (IntCol, CharCol) VALUES (10, 'ten')");
    
    $stmt = $conn->prepare("SELECT IntCol FROM table1");
    $stmt->execute();
    
    // PARAM_NULL returns null
    $stmt->bindColumn('IntCol', $intCol, PDO::PARAM_NULL);
    $row = $stmt->fetch(PDO::FETCH_BOUND);
    if ($intCol == NULL) {
        echo "intCol is NULL\n";
    } else {
        echo "intCol should have been NULL\n";
    }
    
    $stmt = $conn->prepare("SELECT CharCol FROM table1");
    $stmt->execute();
    
    // PARAM_STMT is not supported and should throw an exception
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