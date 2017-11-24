--TEST--
GitHub issue 574 - Fetch Next Result Test
--DESCRIPTION--
Verifies the functionality of PDOStatement nextRowset
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $tableName = 'test574';
    $tableName1 = 'test574_1';

    // create two tables with max fields
    $columns = array(new ColumnMeta('varchar(max)', 'col1'));
    createTable($conn, $tableName, $columns);

    $columns = array(new ColumnMeta('varchar(max)', 'col1'));
    createTable($conn, $tableName1, $columns);

    // insert one row to each table
    $phrase = str_repeat('This is a test ', 25000);
    $stmt = insertRow($conn, $tableName, array('col1' => $phrase));
    unset($stmt);

    $phrase1 = str_repeat('This is indeed very long ', 30000);
    $stmt = insertRow($conn, $tableName1, array('col1' => $phrase1));
    unset($stmt);

    // run queries in a batch
    $stmt = $conn->prepare("SELECT * FROM [$tableName]; SELECT artist FROM [cd_info]; SELECT * FROM [$tableName1]");
    $stmt->execute();
    
    // fetch from $tableName
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        if ($row[0] === $phrase) {
            echo(substr($row[0], 0, 15)) . PHP_EOL;
        } else {
            echo "Incorrect value substr($row[0], 0, 1000)...!" . PHP_EOL;
        }
    }

    // fetch from cd_info
    echo "1. next result? ";
    $next = $stmt->nextRowset();
    var_dump($next);

    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        echo $row[0] . PHP_EOL;
    }

    // fetch from $tableName1
    echo "2. next result? ";
    $next = $stmt->nextRowset();
    var_dump($next);

    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        if ($row[0] === $phrase1) {
            echo(substr($row[0], 0, 25)) . PHP_EOL;
        } else {
            echo "Incorrect value substr($row[0], 0, 1000)...!" . PHP_EOL;
        }
    }

    // should be no more next results, first returns false second throws an exception
    echo "3. next result? ";
    $next = $stmt->nextRowset();
    var_dump($next);

    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        fatalError("This is unexpected!\n");
    }

    echo "4. next result? " . PHP_EOL;
    try {
        $next = $stmt->nextRowset();
    } catch (PDOException $e) {
        echo $e->getMessage() . PHP_EOL;
    }
    
    // run queries in a batch again, different order this time
    $stmt = $conn->prepare("SELECT * FROM [$tableName1]; SELECT * FROM [$tableName]; SELECT artist FROM [cd_info]");
    $stmt->execute();

    // skip the first two queries
    $stmt->nextRowset();
    $stmt->nextRowset();

    // fetch from cd_info
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        echo $row[0] . PHP_EOL;
    }

    // re-execute the statement, should return to the first query in the batch
    $stmt->execute();

    // fetch from $tableName1
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        if ($row[0] === $phrase1) {
            echo(substr($row[0], 0, 25)) . PHP_EOL;
        } else {
            echo "Incorrect value substr($row[0], 0, 1000)...!" . PHP_EOL;
        }
    }
    unset($stmt);

    // execute a simple query, no more batch
    $stmt = $conn->query("SELECT * FROM [$tableName]");

    // fetch from $tableName
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        if ($row[0] === $phrase) {
            echo(substr($row[0], 0, 15)) . PHP_EOL;
        } else {
            echo "Incorrect value substr($row[0], 0, 1000)...!" . PHP_EOL;
        }
    }

    // should be no more next results, first returns false second throws an exception
    echo "5. next result? ";
    $next = $stmt->nextRowset();
    var_dump($next);

    echo "6. next result? " . PHP_EOL;
    try {
        $next = $stmt->nextRowset();
    } catch (PDOException $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    dropTable($conn, $tableName);
    dropTable($conn, $tableName1);

    unset($stmt);
    unset($conn);

    echo "Done\n";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
This is a test 
1. next result? bool(true)
Led Zeppelin
2. next result? bool(true)
This is indeed very long 
3. next result? bool(false)
4. next result? 
SQLSTATE[IMSSP]: There are no more results returned by the query.
Led Zeppelin
This is indeed very long 
This is a test 
5. next result? bool(false)
6. next result? 
SQLSTATE[IMSSP]: There are no more results returned by the query.
Done