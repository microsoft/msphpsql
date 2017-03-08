--TEST--
test rowCount() with different querying method
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

function RowCount_Query($exec)
{
    require("autonomous_setup.php");
    
    $database = "tempdb";
    $tableName = GetTempTableName();
          
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
    
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_int] int, [c2_real] real)");
    
    for ($i = 1; $i < 5; $i++)
    {
        InsertData($conn, $tableName, $i);
    }
     
    for ($i = 1; $i < 5; $i++)
    {
        UpdateData($conn, $tableName, $i, $exec);
    }
    
    DeleteData($conn, $tableName, $exec);
    
    $stmt = null;
    $conn = null;   
}

function InsertData($conn, $tableName, $value)
{
    $query = "INSERT INTO $tableName VALUES ($value, $value * 1.0)";
    $stmt = $conn->query($query);
}

function UpdateData($conn, $tableName, $value, $exec)
{
    $newValue = $value * 100;
    $query = "UPDATE $tableName SET c1_int = $newValue WHERE (c1_int = $value)";
    $rowCount = 0;
    
    if ($exec){
        $rowCount = $conn->exec($query);
    }
    else {            
        $stmt = $conn->prepare($query);
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0)
            echo "Number of rows affected prior to execution should be 0!\n";

        $stmt->execute();
        $rowCount = $stmt->rowCount();
    }
   
    if ($rowCount !== 1)
        echo "Number of rows affected should be 1!\n";
    
    $stmt = null;
}

function DeleteData($conn, $tableName, $exec)
{
    $query = "DELETE TOP(3) FROM $tableName";   
    $rowCount = 0;
    
    if ($exec){
        $rowCount = $conn->exec($query);
    }
    else {                
        $stmt = $conn->query($query);
        $rowCount = $stmt->rowCount();
    }
    
    if ($rowCount <= 0)
        echo "Number of rows affected should be > 0!\n";
    
    $stmt = null;
}

function Repro()
{
    StartTest("pdo_statement_rowcount_query");
    try
    {
        RowCount_Query(true);
        RowCount_Query(false);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_statement_rowcount_query");
}

Repro();

?>
--EXPECT--

...Starting 'pdo_statement_rowcount_query' test...

Done
...Test 'pdo_statement_rowcount_query' completed successfully.
