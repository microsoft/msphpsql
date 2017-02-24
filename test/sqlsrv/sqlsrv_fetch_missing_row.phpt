--TEST--
Fetch missing row
--FILE--
﻿<?php
include 'tools.inc';

function MissingRow_Fetch()
{
    include 'autonomous_setup.php';
       
    set_time_limit(0);  
    sqlsrv_configure('WarningsReturnAsErrors', 1);  
    sqlsrv_get_config('WarningsReturnAsErrors');    
    
    // Connect
    $connectionInfo = array("UID"=>$username, "PWD"=>$password);
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if( !$conn ) { FatalError("Could not connect.\n"); }

    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_timestamp] timestamp, [c28_xml] xml, [c29_time] time, [c30_date] date, [c31_datetime2] datetime2, [c32_datetimeoffset] datetimeoffset)");
    sqlsrv_free_stmt($stmt);
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $result1 = sqlsrv_fetch($stmt);
    $result2 = sqlsrv_fetch($stmt);
    
    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
    $e = $errors[0];    
    $value1 = $e['message'];    
    print "$value1\n";  
    $value2 = $e['code'];   
    print "$value2\n";  
    $value3 = $e['SQLSTATE'];   
    print "$value3\n";  
   
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);           
}

function Repro()
{
    StartTest("sqlsrv_fetch_missing_row");
    try
    {
        MissingRow_Fetch();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_fetch_missing_row");
}

Repro();

?>
--EXPECT--
﻿
...Starting 'sqlsrv_fetch_missing_row' test...
There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.
-22
IMSSP

Done
...Test 'sqlsrv_fetch_missing_row' completed successfully.
