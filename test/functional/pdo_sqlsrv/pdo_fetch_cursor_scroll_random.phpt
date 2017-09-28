--TEST--
Test with cursor scroll and select different rows in some random order 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
﻿﻿<?php
include 'MsCommon.inc';

function Cursor_ForwardOnly($conn, $tableName)
{
    create_table( $conn, $tableName, array( new columnMeta( "int", "c1_int" ),
                                            new columnMeta( "tinyint", "c2_tinyint" ),
                                            new columnMeta( "smallint", "c3_smallint" ),
                                            new columnMeta( "bigint", "c4_bigint" ),
                                            new columnMeta( "bit", "c5_bit" )));

    // insert data
    $numRows = InsertData($conn, $tableName);
    
    // select table 
    if ( !is_col_encrypted() )
        $stmt = $conn->prepare( "SELECT * FROM $tableName ORDER BY c1_int", array( constant( 'PDO::ATTR_CURSOR' ) => PDO::CURSOR_FWDONLY ));
    else
        // ORDER BY is not supported for encrypted columns
        $stmt = $conn->prepare( "SELECT * FROM $tableName", array( constant( 'PDO::ATTR_CURSOR' ) => PDO::CURSOR_FWDONLY ));
    $stmt->execute();

    $numRowsFetched = 0;
    while ($row = $stmt->fetch(PDO::FETCH_NUM))
    {
        echo "$row[0]\n";
        $numRowsFetched++;
    }
    
    if ($numRowsFetched != $numRows)
        echo "Number of rows fetched $numRowsFetched is wrong! Expected $numRows\n";
        
    unset( $stmt );
}

function Cursor_Scroll_FetchRows($conn, $tableName)
{
    if ( !is_col_encrypted() )
        $stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY c1_int", array( PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ));
    else
        // ORDER BY is not supported for encrypted columns
        // scrollable cursor is not supported for encrypted tablee; use client side buffered cursor
        $stmt = $conn->prepare("SELECT * FROM $tableName", array( PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED ));
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
    
    unset( $stmt );
}

function InsertData($conn, $tableName)
{
    $numRows = 0;
    
    insert_row( $conn, $tableName, array( "c1_int" => 1, "c2_tinyint" => 0, "c3_smallint" => null, "c4_bigint" => 922337203685477, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 2, "c2_tinyint" => null, "c3_smallint" => -32768, "c4_bigint" => 922337203685477, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 3, "c2_tinyint" => 255, "c3_smallint" => -32768, "c4_bigint" => 1035941737, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 4, "c2_tinyint" => null, "c3_smallint" => 4762, "c4_bigint" => 804325764, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 5, "c2_tinyint" => 57, "c3_smallint" => 32767, "c4_bigint" => -922337203685477, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 6, "c2_tinyint" => 201, "c3_smallint" => -32768, "c4_bigint" => 450619355, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 7, "c2_tinyint" => 244, "c3_smallint" => -21244, "c4_bigint" => 981345728, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 8, "c2_tinyint" => 143, "c3_smallint" => 0, "c4_bigint" => -1330405117, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 9, "c2_tinyint" => null, "c3_smallint" => null, "c4_bigint" => 209123628, "c5_bit" => 0 ));
    $numRows++;
    
    insert_row( $conn, $tableName, array( "c1_int" => 10, "c2_tinyint" => 147, "c3_smallint" => 21133, "c4_bigint" => -1, "c5_bit" => 0 ));
    $numRows++;
    
    return $numRows;
}

function GetFirstRow($stmt)
{
    echo "first row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST, 0);    
    if ($row)
        echo "$row[0]\n";
}

function GetNextRow($stmt)
{
    echo "next row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT, 0);    
    if ($row)
        echo "$row[0]\n";
}

function GetPriorRow($stmt)
{
    echo "prior row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_PRIOR, 0);    
    if ($row)
        echo "$row[0]\n";
}

function GetLastRow($stmt)
{
    echo "last row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_LAST, 0);    
    if ($row)
        echo "$row[0]\n";
}

function GetRelativeRow($stmt, $offset)
{
    echo "row $offset from the current row: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_REL, $offset);    
    if ($row)
        echo "$row[0]\n";
}

function GetAbsoluteRow($stmt, $offset)
{
    echo "absolute row with offset $offset: ";      
    $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_ABS, $offset);    
    if ($row)
        echo "$row[0]\n";
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
    $tableName = GetTempTableName( '', false );
        
    Cursor_ForwardOnly( $conn, $tableName );
    Cursor_Scroll_FetchRows( $conn, $tableName );

    DropTable( $conn, $tableName );
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