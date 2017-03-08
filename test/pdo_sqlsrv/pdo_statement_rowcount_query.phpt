--TEST--
test rowCount() with different querying method and test nextRowset() with different fetch
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
    
    $numRows = 5;
    for ($i = 1; $i <= $numRows; $i++)
    {
        InsertData($conn, $tableName, $i);
    }
     
    FetchRowsets($conn, $tableName, $numRows);
    
    for ($i = 1; $i <= $numRows; $i++)
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
    
    if ($exec)
    {
        $rowCount = $conn->exec($query);
    }
    else 
    {            
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

function CompareValues($actual, $expected)
{
    if ($actual != $expected)
    {
        echo "Unexpected value $value returned! Expected $expected.\n";
    }
}

function FetchRowsets($conn, $tableName, $numRows)
{
    $query = "SELECT [c1_int] FROM $tableName ORDER BY [c1_int]"; 
    $queries = $query . ';' . $query . ';' . $query;
    $stmt = $conn->query($queries);

    $i = 0;
    while ($row = $stmt->fetch(PDO::FETCH_LAZY))
    {
        $value = (int)$row['c1_int'];
        CompareValues($value, ++$i);
    }
    
    if ($i != $numRows)
    {
        echo "Number of rows fetched $i is unexpected!\n";
    }
    
    $result = $stmt->nextRowset();
    if ($result == false)
    {
        echo "Missing result sets!\n";
    }
    
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    $i = 0;
    foreach ($rows as $row)
    {
        foreach ($row as $key => $value)
        {
            $value = (int)$value;
            CompareValues($value, ++$i);
        }
    }    

    $result = $stmt->nextRowset();
    if ($result == false)
    {
        echo "Missing result sets!\n";
    }
    
    $stmt->bindColumn('c1_int', $value);
    $i = 0;
    while ($row = $stmt->fetch(PDO::FETCH_BOUND))
    {
        CompareValues($value, ++$i);
    }
    
    $result = $stmt->nextRowset();
    if ($result != false)
    {
        echo "Number of result sets exceeding expectation!\n";
    }
}

function DeleteData($conn, $tableName, $exec)
{
    $query = "DELETE TOP(3) FROM $tableName";   
    $rowCount = 0;
    
    if ($exec)
    {
        $rowCount = $conn->exec($query);
    }
    else 
    {                
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
