--TEST--
test query time out at the connection level and statement level
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

function QueryTimeout($connLevel)
{
    require("autonomous_setup.php");
    
    $database = "tempdb";
    $tableName = GetTempTableName();
          
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
    
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_int] int, [c2_varchar] varchar(25))");
    
    $query = "INSERT INTO $tableName ([c1_int], [c2_varchar]) VALUES (1, 'QueryTimeout 1')";
    $stmt = $conn->query($query);
    
    $query = "INSERT INTO $tableName ([c1_int], [c2_varchar]) VALUES (2, 'QueryTimeout 2')";
    $stmt = $conn->query($query);

    $query = "SELECT * FROM $tableName";
    
    if ($connLevel)
    {
        echo "Setting query timeout as an attribute in connection\n";
        $conn->setAttribute(constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT'), 1);
        $stmt = $conn->query("WAITFOR DELAY '00:00:03'; $query");
        
        var_dump($conn->errorInfo());
    }
    else
    {
        echo "Setting query timeout in the statement\n";
        $stmt = $conn->prepare("WAITFOR DELAY '00:00:03'; $query", array(constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT') => 1));
        $stmt->execute();

        var_dump($stmt->errorInfo());
    }
        
    $stmt = null;
    $conn = null;   
}

function Repro()
{
    StartTest("pdo_query_timeout");
    try
    {
        QueryTimeout(true);
        QueryTimeout(false);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_query_timeout");
}

Repro();

?>
--EXPECT--

...Starting 'pdo_query_timeout' test...
Setting query timeout as an attribute in connection
array(3) {
  [0]=>
  string(5) "HYT00"
  [1]=>
  int(0)
  [2]=>
  string(63) "[Microsoft][ODBC Driver 13 for SQL Server]Query timeout expired"
}
Setting query timeout in the statement
array(3) {
  [0]=>
  string(5) "HYT00"
  [1]=>
  int(0)
  [2]=>
  string(63) "[Microsoft][ODBC Driver 13 for SQL Server]Query timeout expired"
}

Done
...Test 'pdo_query_timeout' completed successfully.
