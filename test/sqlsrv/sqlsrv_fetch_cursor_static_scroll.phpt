--TEST--
Test with static cursor and select different rows in some random order 
--FILE--
﻿﻿<?php
include 'tools.inc';

function FetchRow_Query($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar] varchar(10))");
    sqlsrv_free_stmt($stmt);

    // insert data
    $numRows = 10;
    InsertData($conn, $tableName, $numRows);
    
    // select table 
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName", array(), array('Scrollable' => 'static'));

    HasRows($stmt);
    $numRowsFetched = 0;
    while ($obj = sqlsrv_fetch_object($stmt))
    {
        echo $obj->c1_int . ", " . $obj->c2_varchar . "\n";
        $numRowsFetched++;
    }
    
    if ($numRowsFetched != $numRows)
        echo "Number of rows fetched $numRowsFetched is wrong! Expected $numRows\n";

    GetFirstRow($stmt);
    GetNextRow($stmt);
    GetLastRow($stmt);
    GetPriorRow($stmt);
    GetAbsoluteRow($stmt, 7);
    GetAbsoluteRow($stmt, 2);
    GetRelativeRow($stmt, 3);
    GetPriorRow($stmt);
    GetRelativeRow($stmt, -4);
    GetAbsoluteRow($stmt, 0);
    GetNextRow($stmt);
    GetRelativeRow($stmt, 5);
    GetAbsoluteRow($stmt, -1);
}

function InsertData($conn, $tableName, $numRows)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_varchar) VALUES (?, ?)", array(&$v1, &$v2));

    for ($i = 0; $i < $numRows; $i++)
    {
        $v1 = $i + 1;
        $v2 = "Row " . $v1;

        sqlsrv_execute($stmt); 
    }
}

function GetFirstRow($stmt)
{
    echo "\nfirst row: ";      
    $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);       
    if ($result)
    {
        $field1 = sqlsrv_get_field( $stmt, 0 );  
        $field2 = sqlsrv_get_field( $stmt, 1 );  
        echo "$field1, $field2\n";     
    }
}

function GetNextRow($stmt)
{
    echo "\nnext row: ";      
    $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_NEXT);       
    if ($result)
    {
        $field1 = sqlsrv_get_field( $stmt, 0 );  
        $field2 = sqlsrv_get_field( $stmt, 1 );  
        echo "$field1, $field2\n";     
    }
}

function GetPriorRow($stmt)
{
    echo "\nprior row: ";      
    $obj = sqlsrv_fetch_object($stmt, null, null, SQLSRV_SCROLL_PRIOR);       
    if ($obj)
        echo $obj->c1_int . ", " . $obj->c2_varchar . "\n";
}

function GetLastRow($stmt)
{
    echo "\nlast row: ";      
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC, SQLSRV_SCROLL_LAST);
    if ($row)
        echo $row[0] . ", " . $row[1] . "\n";
}

function GetRelativeRow($stmt, $offset)
{
    echo "\nrow $offset from the current row: ";      
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_RELATIVE, $offset);
    if ($row)
        echo $row['c1_int'] . ", " . $row['c2_varchar'] . "\n";
}

function GetAbsoluteRow($stmt, $offset)
{
    echo "\nabsolute row with offset $offset: ";      
    $obj = sqlsrv_fetch_object($stmt, null, null, SQLSRV_SCROLL_ABSOLUTE, $offset);       
    if ($obj)
        echo $obj->c1_int . ", " . $obj->c2_varchar . "\n";
}

function DisplayRow($stmt)
{
    $field1 = sqlsrv_get_field( $stmt, 0 );  
    $field2 = sqlsrv_get_field( $stmt, 1 );  
    echo "$field1, $field2\n";     
}

function HasRows($stmt)
{
    $rows = sqlsrv_has_rows( $stmt );  
    if ( $rows != true ) 
       echo "Should have rows!\n";  
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    StartTest("sqlsrv_fetch_cursor_static_scroll");
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

        //echo "\nUsing SQLSRV_CURSOR_FORWARD: ";      
        FetchRow_Query($conn);

        sqlsrv_close($conn);    
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_fetch_cursor_static_scroll");
}

RunTest();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_fetch_cursor_static_scroll' test...
1, Row 1
2, Row 2
3, Row 3
4, Row 4
5, Row 5
6, Row 6
7, Row 7
8, Row 8
9, Row 9
10, Row 10

first row: 1, Row 1

next row: 2, Row 2

last row: 10, Row 10

prior row: 9, Row 9

absolute row with offset 7: 8, Row 8

absolute row with offset 2: 3, Row 3

row 3 from the current row: 6, Row 6

prior row: 5, Row 5

row -4 from the current row: 1, Row 1

absolute row with offset 0: 1, Row 1

next row: 2, Row 2

row 5 from the current row: 7, Row 7

absolute row with offset -1: 
Done
...Test 'sqlsrv_fetch_cursor_static_scroll' completed successfully.