--TEST--
Verify Github Issue 1307 is fixed but TVP and table are defined in a different schema
--DESCRIPTION--
To show that table-valued parameters work with non-procedure statements
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

function cleanup($conn, $tvpname, $testTable, $schema)
{
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    $dropTableType = dropTableTypeSQL($conn, $tvpname, $schema);
    $conn->exec($dropTableType);
    $conn->exec("DROP TABLE IF EXISTS [$schema].[$testTable]");
    $conn->exec("DROP SCHEMA IF EXISTS [$schema]");

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

function readData($conn, $schema, $testTable)
{
    $tsql = "SELECT id FROM [$schema].[$testTable] ORDER BY id";
    $stmt = $conn->query($tsql);
    $stmt->bindColumn('id', $ID);  
    while ($row = $stmt->fetch( PDO::FETCH_BOUND ) ){  
        echo $ID . PHP_EOL;
    }
}

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tvpname = 'pdo_id_table2';
    $testTable = 'pdo_test_table2';
    $schema = 'pdo schema';
    
    cleanup($conn, $tvpname, $testTable, $schema);

    // Create the schema
    $tsql = "CREATE SCHEMA [$schema]";
    $conn->exec($tsql);

    // Create the table type and test table
    $tsql = "CREATE TYPE [$schema].[$tvpname] AS TABLE(id INT PRIMARY KEY)";
    $conn->exec($tsql);

    $tsql = "CREATE TABLE [$schema].[$testTable] (id INT PRIMARY KEY)";
    $conn->exec($tsql);
    
    // Populate the table using the table type
    $tsql = "INSERT INTO [$schema].[$testTable] SELECT * FROM ?";
    $tvpinput = array($tvpname => [[5], [3], [1]], $schema);

    $stmt = $conn->prepare($tsql);
    $stmt->bindParam(1, $tvpinput, PDO::PARAM_LOB);
    $result = $stmt->execute();
    
    // Verify the results
    readData($conn, $schema, $testTable);
    
    // Use Merge statement next
    $tsql = <<<QRY
    MERGE INTO [$schema].[$testTable] t
    USING ? s ON s.id = t.id
    WHEN NOT MATCHED THEN 
    INSERT (id) VALUES(s.id);
QRY;

    unset($tvpinput);
    $tvpinput = array($tvpname => [[2], [4], [6], [7]], $schema);

    $stmt = $conn->prepare($tsql);
    $stmt->bindParam(1, $tvpinput, PDO::PARAM_LOB);
    $result = $stmt->execute();
    
    // Verify the results
    readData($conn, $schema, $testTable);

    cleanup($conn, $tvpname, $testTable, $schema);
    
    echo "Done\n";
    
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
1
3
5
1
2
3
4
5
6
7
Done