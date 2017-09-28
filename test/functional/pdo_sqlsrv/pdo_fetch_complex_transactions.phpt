--TEST--
Test transactions commit, rollback and aborting in between
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
﻿﻿<?php
include 'MsCommon.inc';

function ComplexTransaction($conn, $tableName)
{
    create_table( $conn, $tableName, array( new columnMeta( "int", "c1_int" ), new columnMeta( "real", "c2_real" )));

    $stmtSelect = $conn->prepare("SELECT * FROM $tableName");
    $stmtDelete = $conn->prepare("DELETE TOP(3) FROM $tableName");
   
    // insert ten rows
    $numRows = 10;
    InsertData( $conn, $tableName, $numRows );
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

echo "Test begins...\n";
try
{
    // Connect
    $conn = connect();
    $conn2 = connect();

    $tableName = GetTempTableName( 'testTransaction', false );

    // ComplexTransaction() returns number of rows left in $tableName
    $numRows = ComplexTransaction( $conn, $tableName );
    // disconnect first connection, transaction aborted
    unset( $conn );

    // select table using the second connection
    $stmt = $conn2->prepare("SELECT * FROM $tableName");
    FetchData($stmt, $tableName, $numRows, true);

    // drop test table
    DropTable( $conn2, $tableName );
    unset( $conn );   
}
catch (Exception $e)
{
    echo $e->getMessage();
}
echo "Done\n";

?>
--EXPECT--
Test begins...
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