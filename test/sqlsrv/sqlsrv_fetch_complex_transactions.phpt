--TEST--
Populate different test tables with binary fields using empty stream data as inputs
--FILE--
﻿﻿<?php
include 'tools.inc';

function ComplexTransaction($conn, $conn2)
{
    $tableName = GetTempTableName('testTransaction');
    
    // create a test table with a binary(512) column
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_real] real)");
    sqlsrv_free_stmt($stmt);

    $stmtSelect = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    $stmtDelete = sqlsrv_prepare($conn, "DELETE TOP(3) FROM $tableName");
   
    // insert ten rows
    $numRows = 10;
    InsertData($conn, $tableName, $numRows);
    FetchData($stmtSelect, $tableName, $numRows);
    
    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);    
    $rowsAffected = sqlsrv_rows_affected($stmtDelete);
    sqlsrv_commit($conn);
    echo "Committed deleting 3 rows\n";
    
    $numRows = $numRows - $rowsAffected;
    FetchData($stmtSelect, $tableName, $numRows);
    
    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete); 
    sqlsrv_rollback($conn);
    echo "Rolled back\n";
    
    FetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete); 
    sqlsrv_commit($conn);
    echo "Committed deleting 3 rows\n";
    
    $numRows = $numRows - $rowsAffected;
    FetchData($stmtSelect, $tableName, $numRows);
    
    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete); 
    sqlsrv_rollback($conn);
    echo "Rolled back\n";
    
    FetchData($stmtSelect, $tableName, $numRows);

    sqlsrv_begin_transaction($conn);
    sqlsrv_execute($stmtDelete);     
    // disconnect first connection, transaction aborted
    sqlsrv_close($conn);
    echo "Deletion aborted\n";
    
    // select table using the second connection
    $stmt = sqlsrv_prepare($conn2, "SELECT * FROM $tableName");
    FetchData($stmt, $tableName, $numRows);
    
    sqlsrv_query($conn2, "DROP TABLE $tableName");
}

function InsertData($conn, $tableName, $count)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_real) VALUES (?, ?)", array(&$v1, &$v2));

    for ($i = 0; $i < $count; $i++)
    {
        $v1 = $i + 1;
        $v2 = $v1 * 1.5;

        sqlsrv_execute($stmt); 
    }
}

function FetchData($stmt, $tableName, $numRows)
{
    $numFetched = 0;
    sqlsrv_execute($stmt);
    while ($result = sqlsrv_fetch($stmt))
    {
        $numFetched++;
    }
    
    echo "Number of rows fetched: $numFetched\n";
    if ($numFetched != $numRows)
    {
        echo "Expected $numRows rows.\n";
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_fetch_complex_transactions");
    try
    {
        set_time_limit(0);  
        sqlsrv_configure('WarningsReturnAsErrors', 1);  

        require_once("autonomous_setup.php");
        $database = "tempdb";
        
        // Connect
        $connectionInfo = array('Database'=>$database, 'UID'=>$username, 'PWD'=>$password);
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if( !$conn ) { FatalError("Could not connect.\n"); }

        $conn2 = sqlsrv_connect($serverName, $connectionInfo);
        if( !$conn2 ) { FatalError("Could not connect.\n"); }
        
        ComplexTransaction($conn, $conn2);

        sqlsrv_close($conn2);    // $conn should have been closed                
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_fetch_complex_transactions");
}

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_fetch_complex_transactions' test...
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
...Test 'sqlsrv_fetch_complex_transactions' completed successfully.