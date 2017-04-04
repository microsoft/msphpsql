--TEST--
Test transactions commit, rollback and aborting in between
--FILE--
﻿﻿<?php
include 'pdo_tools.inc';

function ComplexTransaction($conn, $tableName)
{
    $stmt = $conn->query("CREATE TABLE $tableName ([c1_int] int, [c2_real] real)");
    $stmt = null;

    $stmtSelect = $conn->prepare("SELECT * FROM $tableName");
    $stmtDelete = $conn->prepare("DELETE TOP(3) FROM $tableName");
   
    // insert ten rows
    $numRows = 10;
    InsertData($conn, $tableName, $numRows);
    FetchData($stmtSelect, $tableName, $numRows);
    
    $conn->beginTransaction();
    $stmtDelete->execute(); 
    $rowsAffected = $stmtDelete->rowCount();
    $conn->commit();
    echo "Committed deleting 3 rows\n";
    
    $numRows = $numRows - $rowsAffected;
    FetchData($stmtSelect, $tableName, $numRows);
    
    $conn->beginTransaction();
    $stmtDelete->execute(); 
    $conn->rollback();
    echo "Rolled back\n";
    
    FetchData($stmtSelect, $tableName, $numRows);

    $conn->beginTransaction();
    $stmtDelete->execute(); 
    $rowsAffected = $stmtDelete->rowCount();
    $conn->commit();
    echo "Committed deleting 3 rows\n";
    
    $numRows = $numRows - $rowsAffected;
    FetchData($stmtSelect, $tableName, $numRows);
    
    $conn->beginTransaction();
    $stmtDelete->execute(); 
    $conn->rollback();
    echo "Rolled back\n";
    
    FetchData($stmtSelect, $tableName, $numRows);

    $conn->beginTransaction();
    $stmtDelete->execute(); 

    echo "Deletion aborted\n";  

    return $numRows;    
}

function InsertData($conn, $tableName, $count)
{
    $stmt = $conn->prepare("INSERT INTO $tableName (c1_int, c2_real) VALUES (?, ?)");

    for ($i = 0; $i < $count; $i++)
    {
        $v1 = $i + 1;
        $v2 = $v1 * 1.5;

        $stmt->bindValue(1, $v1);
        $stmt->bindValue(2, $v2);
        $stmt->execute(); 
    }
}

function FetchData($stmt, $tableName, $numRows, $fetchMode = false)
{
    $numFetched = 0;
    $stmt->execute(); 
    if ($fetchMode)
    {
        $stmt->setFetchMode(PDO::FETCH_LAZY);  
        while ($result = $stmt->fetch())
            $numFetched++;      
    }
    else
    {
        while ($result = $stmt->fetch(PDO::FETCH_LAZY))
            $numFetched++;
    }
    
    echo "Number of rows fetched: $numFetched\n";
    if ($numFetched != $numRows)
    {
        echo "Expected $numRows rows.\n";
    }
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    StartTest("pdo_fetch_complex_transactions.phpt");
    try
    {
        require_once("autonomous_setup.php");
        $database = "tempdb";
        
        // Connect
        $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        
        $conn2 = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
        $conn2->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        $tableName = GetTempTableName('testTransaction');

        // ComplexTransaction() returns number of rows left in $tableName
        $numRows = ComplexTransaction($conn, $tableName);
        // disconnect first connection, transaction aborted
        $conn = null;

        // select table using the second connection
        $stmt = $conn2->prepare("SELECT * FROM $tableName");
        FetchData($stmt, $tableName, $numRows, true);

        // drop test table
        $conn2->query("DROP TABLE $tableName");
        $conn2 = null;    
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_fetch_complex_transactions.phpt");
}

RunTest();

?>
--EXPECT--
﻿﻿
...Starting 'pdo_fetch_complex_transactions.phpt' test...
Number of rows fetched: 10
Committed deleting 3 rows
Number of rows fetched: 7
Rolled back
Number of rows fetched: 7
Committed deleting 3 rows
Number of rows fetched: 4
Rolled back
Number of rows fetched: 4
Deletion aborted
Number of rows fetched: 4

Done
...Test 'pdo_fetch_complex_transactions.phpt' completed successfully.