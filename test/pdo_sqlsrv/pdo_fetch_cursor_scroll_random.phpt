--TEST--
Test with cursor scroll and select different rows in some random order 
--FILE--
﻿﻿<?php
include 'pdo_tools.inc';

function Cursor_ForwardOnly($conn, $tableName)
{
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit)");

    // insert data
    $numRows = InsertData($conn, $tableName);
    
    // select table 
    $stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY c1_int", array(constant('PDO::ATTR_CURSOR') => PDO::CURSOR_FWDONLY));
    $stmt->execute();

    $numRowsFetched = 0;
    while ($row = $stmt->fetch(PDO::FETCH_NUM))
    {
        echo "$row[0]\n";
        $numRowsFetched++;
    }
    
    if ($numRowsFetched != $numRows)
        echo "Number of rows fetched $numRowsFetched is wrong! Expected $numRows\n";
}

function Cursor_Scroll_FetchRows($conn, $tableName)
{
    $stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY c1_int", array(constant('PDO::ATTR_CURSOR') => PDO::CURSOR_SCROLL));
    $stmt->execute();
    
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
    GetNextRow($stmt);
    GetLastRow($stmt);
    GetRelativeRow($stmt, 1);
}

function InsertData($conn, $tableName)
{
    $numRows = 0;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((1), (0), (null), (9223372036854775807), (0))");   
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((2), (null), (-32768), (9223372036854775807), (0))");  
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((3), (255), (-32768), (1035941737), (0))");    
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((4), (null), (4762), (804325764), (0))");  
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((5), (57), (32767), (-9223372036854775808), (0))");    
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((6), (201), (-32768), (450619355), (0))"); 
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((7), (244), (-21244), (981345728), (0))"); 
    $numRows += $count;   
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((8), (143), (0), (-1330405117), (0))");    
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((9), (null), (null), (209123628), (0))");  
    $numRows += $count;
    $count = $conn->exec("INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit) VALUES ((10), (147), (21133), (-1), (0))");
    $numRows += $count;
    
    return $numRows;
}

function GetFirstRow($stmt)
{
    echo "\nfirst row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST, 0);    
    if ($row)
    {
        echo "$row[0]\n";     
    }
}

function GetNextRow($stmt)
{
    echo "\nnext row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT, 0);    
    if ($row)
    {
        echo "$row[0]\n";     
    }
}

function GetPriorRow($stmt)
{
    echo "\nprior row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_PRIOR, 0);    
    if ($row)
    {
        echo "$row[0]\n";     
    }
}

function GetLastRow($stmt)
{
    echo "\nlast row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_LAST, 0);    
    if ($row)
    {
        echo "$row[0]\n";     
    }
}

function GetRelativeRow($stmt, $offset)
{
    echo "\nrow $offset from the current row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_REL, $offset);    
    if ($row)
    {
        echo "$row[0]\n";     
    }
}

function GetAbsoluteRow($stmt, $offset)
{
    echo "\nabsolute row with offset $offset: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_ABS, $offset);    
    if ($row)
    {
        echo "$row[0]\n";     
    }
}


//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    StartTest("pdo_fetch_cursor_scroll_random");
    try
    {
        require_once("autonomous_setup.php");
        $database = "tempdb";
        
        // Connect
        $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        $tableName = GetTempTableName();
        
        Cursor_ForwardOnly($conn, $tableName);
        Cursor_Scroll_FetchRows($conn, $tableName);

        $conn = null;     
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_fetch_cursor_scroll_random");
}

RunTest();

?>
--EXPECT--
﻿﻿
...Starting 'pdo_fetch_cursor_scroll_random' test...
1
2
3
4
5
6
7
8
9
10

first row: 1

next row: 2

last row: 10

prior row: 9

absolute row with offset 7: 8

absolute row with offset 2: 3

row 3 from the current row: 6

prior row: 5

row -4 from the current row: 1

absolute row with offset 0: 1

next row: 2

row 5 from the current row: 7

absolute row with offset -1: 
next row: 1

last row: 10

row 1 from the current row: 
Done
...Test 'pdo_fetch_cursor_scroll_random' completed successfully.