--TEST--
Test various Katmai types, like geography, geometry, hierarchy, sparse, etc. and fetch them back as strings
--FILE--
﻿﻿<?php
include 'pdo_tools.inc';

function Katmai_Basic_Types($conn)
{
    $tableName = GetTempTableName();
        
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_time] time, [c2_date] date, [c3_datetimeoffset] datetimeoffset, [c4_geography] geography, [c5_geometry] geometry, [c6_hierarchyid] hierarchyid, [c7_uniqueidentifier] uniqueidentifier ROWGUIDCOL NOT NULL UNIQUE DEFAULT NEWID())");   
    $stmt = null;   
    
    $query = "INSERT INTO $tableName ([c1_time], [c2_date], [c3_datetimeoffset], [c4_geography], [c5_geometry], [c6_hierarchyid], [c7_uniqueidentifier]) VALUES (('03:32:25.5643401'), ('1439-01-10'), ('0221-01-12 06:39:07.0620256+00:00'), ('POINT(27.91 -76.74)'), ('LINESTRING(30.50 -0.66, 31.03 -0.38)'), ('/1/3/'), ('5a1a88f7-3749-46a3-8a7a-efae73efe88f'))";
    $stmt = $conn->query($query);
    $stmt = null;   

    echo "\nShowing results of Katmai basic fields\n";
    
    $stmt = $conn->query("SELECT * FROM $tableName");  
    $numFields = $stmt->columnCount();    
    $cols = array_fill(0, $numFields, "");
    
    for ($i = 0; $i < $numFields; $i++)
    {
        $stmt->bindColumn($i+1, $cols[$i]);           
    }
    
    $stmt->fetch(PDO::FETCH_BOUND);
    for ($i = 0; $i < $numFields; $i++)
    {
        $value = $cols[$i]; 
        if ($i >= 3)
        {        
            if ($value != null)
                $value = bin2hex($value);
        }

        var_dump($value);               
    }    
}

function Katmai_SparseChar($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = $conn->exec("CREATE TABLE $tableName (c1 int, c2 char(512) SPARSE NULL, c3 varchar(512) SPARSE NULL, c4 varchar(max) SPARSE NULL, c5 nchar(512) SPARSE NULL, c6 nvarchar(512) SPARSE NULL, c7 nvarchar(max) SPARSE NULL)");   
    $stmt = null;   

    $input = "The quick brown fox jumps over the lazy dog";
    $stmt = $conn->query("INSERT INTO $tableName (c1, c2, c5) VALUES(1, 'The quick brown fox jumps over the lazy dog', 'The quick brown fox jumps over the lazy dog')");  
    $stmt = null;   
    $stmt = $conn->query("INSERT INTO $tableName (c1, c3, c6) VALUES(2, 'The quick brown fox jumps over the lazy dog', 'The quick brown fox jumps over the lazy dog')");  
    $stmt = null;   
    $stmt = $conn->query("INSERT INTO $tableName (c1, c4, c7) VALUES(3, 'The quick brown fox jumps over the lazy dog', 'The quick brown fox jumps over the lazy dog')");  
    $stmt = null;   
 
    echo "\nComparing results of Katmai sparse fields\n";
    $stmt = $conn->query("SELECT * FROM $tableName");   
   
    while ($row = $stmt->fetch(PDO::FETCH_NUM))
    {
        $fld1 = $row[0];
        $fld2 = $fld1 + 3;
        
        $value1 = $row[$fld1];
        $value2 = $row[$fld2];

        if ($input !== trim($value1))
        {
            echo "The value is unexpected!\n";
        }
        if ($value1 !== $value2)
        {
            echo "The values don't match!\n";
        }
    }
}

function Katmai_SparseNumeric($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = $conn->exec("CREATE TABLE $tableName (c1 int, c2 int SPARSE NULL, c3 tinyint SPARSE NULL, c4 smallint SPARSE NULL, c5 bigint SPARSE NULL, c6 bit SPARSE NULL, c7 float SPARSE NULL, c8 real SPARSE NULL, c9 decimal(28,4) SPARSE NULL, c10 numeric(32,4) SPARSE NULL)");
    
    $stmt = $conn->query("INSERT INTO $tableName (c1, c2, c3, c4, c5, c6, c7, c8, c9, c10) VALUES(1, '1', '1', '1', '1', '1', '1', '1', '1', '1')");   
    $stmt = null;
    
    echo "\nShowing results of Katmai sparse numeric fields\n";
    $stmt = $conn->query("SELECT * FROM $tableName");   
    $row = $stmt->fetch(PDO::FETCH_NUM);       
    foreach ($row as $value)
    {
        var_dump($value);
    }    
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("pdo_katmai_special_types");
    try
    {
        require_once("autonomous_setup.php");
        
        set_time_limit(0);
        $database = "tempdb";
        
        $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
    
        Katmai_Basic_Types($conn);
        Katmai_SparseChar($conn);
        Katmai_SparseNumeric($conn);

        $conn = null;   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    
    EndTest("pdo_katmai_special_types");
}

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'pdo_katmai_special_types' test...

Showing results of Katmai basic fields
string(16) "03:32:25.5643401"
string(10) "1439-01-10"
string(34) "0221-01-12 06:39:07.0620256 +00:00"
string(44) "e6100000010c8fc2f5285c2f53c0295c8fc2f5e83b40"
string(76) "0000000001140000000000803e401f85eb51b81ee5bf48e17a14ae073f4052b81e85eb51d8bf"
string(4) "5bc0"
string(72) "35413141383846372d333734392d343641332d384137412d454641453733454645383846"

Comparing results of Katmai sparse fields

Showing results of Katmai sparse numeric fields
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(1) "1"
string(3) "1.0"
string(3) "1.0"
string(6) "1.0000"
string(6) "1.0000"

Done
...Test 'pdo_katmai_special_types' completed successfully.