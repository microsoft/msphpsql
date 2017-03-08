--TEST--
fetch multiple result sets with MARS on and then off
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

function NestedQuery_Mars($on)
{
    require("autonomous_setup.php");
    
    $database = "tempdb";
    $tableName = GetTempTableName();
          
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database;MultipleActiveResultSets=$on", $username, $password);
    $conn->SetAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_int] int, [c2_varchar] varchar(20))");
    
    $query = "INSERT INTO $tableName ([c1_int], [c2_varchar]) VALUES (1, 'Dummy value 1')";
    $stmt = $conn->query($query);
    
    $query = "INSERT INTO $tableName ([c1_int], [c2_varchar]) VALUES (2, 'Dummy value 2')";
    $stmt = $conn->query($query);

    $query = "SELECT * FROM $tableName ORDER BY [c1_int]";
    $stmt = $conn->query($query);
    $numRows = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        $numRows++;

    if ($numRows !== 2) echo "Number of rows is unexpected!\n";
    $stmt = null;

    // more than one active results
    $stmt1 = $conn->query($query);
    $stmt2 = $conn->prepare($query);
    $stmt2->execute();
            
    echo "\nNumber of columns in First set: " . $stmt2->columnCount() . "\n";
    while ($row = $stmt1->fetch(PDO::FETCH_ASSOC))
    {
        print_r($row);        
    }

    echo "\nNumber of columns in Second set: " . $stmt1->columnCount() . "\n\n";
    while ($row = $stmt2->fetch(PDO::FETCH_OBJ))
    {
        print_r($row);        
    }
    
    $stmt1 = null;  
    $stmt2 = null;  
    $conn = null;   
}

function Repro()
{
    StartTest("pdo_nested_query_mars");
    try
    {
        NestedQuery_Mars(true);
        NestedQuery_Mars(false);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_nested_query_mars");
}

Repro();

?>
--EXPECT--

...Starting 'pdo_nested_query_mars' test...

Number of columns in First set: 2
Array
(
    [c1_int] => 1
    [c2_varchar] => Dummy value 1
)
Array
(
    [c1_int] => 2
    [c2_varchar] => Dummy value 2
)

Number of columns in Second set: 2

stdClass Object
(
    [c1_int] => 1
    [c2_varchar] => Dummy value 1
)
stdClass Object
(
    [c1_int] => 2
    [c2_varchar] => Dummy value 2
)
SQLSTATE[IMSSP]: The connection cannot process this operation because there is a statement with pending results.  To make the connection available for other queries, either fetch all results or cancel or free the statement.  For more information, see the product documentation about the MultipleActiveResultSets connection option.
Done
...Test 'pdo_nested_query_mars' completed successfully.
