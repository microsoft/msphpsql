--TEST--
Test various cursor types and whether they reflect changes in the database
--FILE--
﻿﻿<?php
include 'tools.inc';

function Fetch_WithCursor($conn, $cursorType)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_char] char(10))");
    sqlsrv_free_stmt($stmt);

    // insert four rows
    $numRows = 4;
    InsertData($conn, $tableName, 0, $numRows);
    
    // select table 
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName", array(), array('Scrollable' => $cursorType));
    sqlsrv_execute($stmt);

    GetNumRows($stmt, $cursorType);
    $numRowsFetched = 0;
    while ($obj = sqlsrv_fetch_object($stmt))
    {
        echo $obj->c1_int . "\n";
        $numRowsFetched++;
    }
    
    if ($numRowsFetched != $numRows)
        echo "Number of rows fetched $numRowsFetched is wrong! Expected $numRows\n";

    DeleteThenFetchLastRow($conn, $stmt, $tableName, 4);    
}

function InsertData($conn, $tableName, $start, $count)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_char) VALUES (?, ?)", array(&$v1, &$v2));

    $numRows = $start + $count;
    for ($i = $start; $i < $numRows; $i++)
    {
        $v1 = $i + 1;
        $v2 = "Row " . $v1;

        sqlsrv_execute($stmt); 
    }
}

function DeleteThenFetchLastRow($conn, $stmt, $tableName, $id)
{
    echo "\nNow delete the last row then try to fetch it...\n";      
    $stmt2 = sqlsrv_query( $conn, "DELETE FROM $tableName WHERE [c1_int] = 4" );  
    if ( $stmt2 !== false ) {   
       sqlsrv_free_stmt( $stmt2 );   
    }  
    
    $result = sqlsrv_fetch( $stmt, SQLSRV_SCROLL_LAST );  
    if ($result)
    {
        $field1 = sqlsrv_get_field( $stmt, 0 );  
        $field2 = sqlsrv_get_field( $stmt, 1 );  
        var_dump($field1); 
        var_dump($field2); 
    }
    else 
    {
        var_dump($result);
    }
}

function GetNumRows($stmt, $cursorType)
{
    $expectedToFail = false;
    if ($cursorType == SQLSRV_CURSOR_FORWARD || $cursorType == SQLSRV_CURSOR_DYNAMIC)
        $expectedToFail = true;
    
    $rowCount = 0;
    $rowCount = sqlsrv_num_rows( $stmt );  
    if ($expectedToFail)
    {
        if ($rowCount === false)  
        {
            echo "Error occurred in sqlsrv_num_rows, which is expected\n";  
        }
        else 
        {
            echo "sqlsrv_num_rows expected to fail!\n";
        }
    }
    else
    {
        if ($rowCount === false)  
        {
            echo "Error occurred in sqlsrv_num_rows, which is unexpected!\n";  
        }
        else 
        {
            echo "Number of rows: $rowCount\n";
        }
    }
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    StartTest("sqlsrv_fetch_cursor_types");
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

        echo "\nUsing SQLSRV_CURSOR_FORWARD...\n";      
        Fetch_WithCursor($conn, SQLSRV_CURSOR_FORWARD);
        echo "\nUsing SQLSRV_CURSOR_DYNAMIC...\n";      
        Fetch_WithCursor($conn, SQLSRV_CURSOR_DYNAMIC);
        echo "\nUsing SQLSRV_CURSOR_KEYSET...\n";      
        Fetch_WithCursor($conn, SQLSRV_CURSOR_KEYSET);
        echo "\nUsing SQLSRV_CURSOR_STATIC...\n";      
        Fetch_WithCursor($conn, SQLSRV_CURSOR_STATIC);

        sqlsrv_close($conn);    
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_fetch_cursor_types");
}

RunTest();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_fetch_cursor_types' test...

Using SQLSRV_CURSOR_FORWARD...
Error occurred in sqlsrv_num_rows, which is expected
1
2
3
4

Now delete the last row then try to fetch it...
bool(false)

Using SQLSRV_CURSOR_DYNAMIC...
Error occurred in sqlsrv_num_rows, which is expected
1
2
3
4

Now delete the last row then try to fetch it...
int(3)
string(10) "Row 3     "

Using SQLSRV_CURSOR_KEYSET...
Number of rows: 4
1
2
3
4

Now delete the last row then try to fetch it...
bool(false)
bool(false)

Using SQLSRV_CURSOR_STATIC...
Number of rows: 4
1
2
3
4

Now delete the last row then try to fetch it...
int(4)
string(10) "Row 4     "

Done
...Test 'sqlsrv_fetch_cursor_types' completed successfully.