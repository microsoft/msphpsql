--TEST--
Verify Github Issue 1307 is fixed.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

function cleanup($conn, $tvpname, $testTable)
{
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    $conn->exec("DROP TYPE [$tvpname]");
    $conn->exec("DROP TABLE [$testTable]");

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tvpname = 'id_table';
    $testTable = 'test_table';
    
    cleanup($conn, $tvpname, $testTable);

    // Create the table type and test table
    $tsql = "CREATE TYPE $tvpname AS TABLE(id INT PRIMARY KEY)";
    $conn->exec($tsql);

    $tsql = "CREATE TABLE $testTable (id INT PRIMARY KEY)";
    $conn->exec($tsql);
    
    // Populate the table using the table type
    $tsql = "INSERT INTO $testTable SELECT * FROM ?";
    $tvpinput = array($tvpname => [[1], [2], [3]]);

    $stmt = $conn->prepare($tsql);
    $stmt->bindParam(1, $tvpinput, PDO::PARAM_LOB);
    $result = $stmt->execute();
    
    // Verify the results
    $tsql = "SELECT id FROM $testTable ORDER BY id";
    $stmt = $conn->query($tsql);
    $stmt->bindColumn('id', $ID);  
    while ($row = $stmt->fetch( PDO::FETCH_BOUND ) ){  
        echo $ID . PHP_EOL;
    }
    
    // Use Merge statement next
    $tsql = <<<QRY
    MERGE INTO $testTable t
    USING ? s ON s.id = t.id
    WHEN NOT MATCHED THEN 
    INSERT (id) VALUES(s.id);
QRY;

    unset($tvpinput);
    $tvpinput = array($tvpname => [[5], [4], [3], [2]]);

    $stmt = $conn->prepare($tsql);
    $stmt->bindParam(1, $tvpinput, PDO::PARAM_LOB);
    $result = $stmt->execute();
    
    // Verify the results
    $tsql = "SELECT id FROM $testTable ORDER BY id";
    $stmt = $conn->query($tsql);
    $stmt->bindColumn('id', $ID);  
    while ($row = $stmt->fetch( PDO::FETCH_BOUND ) ){  
        echo $ID . PHP_EOL;
    }

    cleanup($conn, $tvpname, $testTable);
    
    echo "Done\n";
    
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
1
2
3
1
2
3
4
5
Done