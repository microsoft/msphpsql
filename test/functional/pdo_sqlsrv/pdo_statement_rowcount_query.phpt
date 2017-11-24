--TEST--
test rowCount() with different querying method and test nextRowset() with different fetch
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
include_once("MsCommon_mid-refactor.inc");

function rowCountQuery($exec)
{
    $conn = connect();

    $tableName = getTableName('testRowCount');
    createTable($conn, $tableName, array("c1_int" => "int", "c2_real" => "real"));

    $numRows = 5;
    for ($i = 1; $i <= $numRows; $i++) {
        $r = $i * 1.0;
        insertRow($conn, $tableName, array("c1_int" => $i, "c2_real" => $r));
    }

    fetchRowsets($conn, $tableName, $numRows);

    for ($i = 1; $i <= $numRows; $i++) {
        updateData($conn, $tableName, $i, $exec);
    }

    deleteData($conn, $tableName, $exec);

    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
}

function updateData($conn, $tableName, $value, $exec)
{
    $newValue = $value * 100;
    $rowCount = 0;

    if (isColEncrypted()) {
        // need to bind parameters for updating encrypted columns
        $query = "UPDATE $tableName SET c1_int = ? WHERE (c1_int = ?)";
        $stmt = $conn->prepare($query);
        if ($rowCount > 0) {
            echo "Number of rows affected prior to execution should be 0!\n";
        }
        $stmt->bindParam(1, $newValue);
        $stmt->bindParam(2, $value);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
    } else {
        $query = "UPDATE $tableName SET c1_int = $newValue WHERE (c1_int = $value)";
        if ($exec) {
            $rowCount = $conn->exec($query);
        } else {
            $stmt = $conn->prepare($query);
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                echo "Number of rows affected prior to execution should be 0!\n";
            }

            $stmt->execute();
            $rowCount = $stmt->rowCount();
        }
    }
    if ($rowCount !== 1) {
        echo "Number of rows affected should be 1!\n";
    }
    unset($stmt);
}

function compareValues($actual, $expected)
{
    if ($actual != $expected) {
        echo "Unexpected value $value returned! Expected $expected.\n";
    }
}

function fetchRowsets($conn, $tableName, $numRows)
{
    if (!isColEncrypted()) {
        $query = "SELECT [c1_int] FROM $tableName ORDER BY [c1_int]";
    } else {
        // ORDER BY is not supported in encrypted columns
        $query = "SELECT [c1_int] FROM $tableName";
    }
    $queries = $query . ';' . $query . ';' . $query;
    $stmt = $conn->query($queries);

    $i = 0;
    while ($row = $stmt->fetch(PDO::FETCH_LAZY)) {
        $value = (int)$row['c1_int'];
        compareValues($value, ++$i);
    }

    if ($i != $numRows) {
        echo "Number of rows fetched $i is unexpected!\n";
    }

    $result = $stmt->nextRowset();
    if ($result == false) {
        echo "Missing result sets!\n";
    }

    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    $i = 0;
    foreach ($rows as $row) {
        foreach ($row as $key => $value) {
            $value = (int)$value;
            compareValues($value, ++$i);
        }
    }

    $result = $stmt->nextRowset();
    if ($result == false) {
        echo "Missing result sets!\n";
    }

    $stmt->bindColumn('c1_int', $value);
    $i = 0;
    while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
        compareValues($value, ++$i);
    }

    $result = $stmt->nextRowset();
    if ($result != false) {
        echo "Number of result sets exceeding expectation!\n";
    }
}

function deleteData($conn, $tableName, $exec)
{
    $query = "DELETE TOP(3) FROM $tableName";
    $rowCount = 0;

    if ($exec) {
        $rowCount = $conn->exec($query);
    } else {
        $stmt = $conn->query($query);
        $rowCount = $stmt->rowCount();
    }

    if ($rowCount <= 0) {
        echo "Number of rows affected should be > 0!\n";
    }

    unset($stmt);
}

echo "Starting test...\n";
try {
    rowCountQuery(true);
    rowCountQuery(false);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "Done\n";
?>
--EXPECT--
Starting test...
Done
