--TEST--
Number of rows in a result set
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    // Create table
    $tableName = getTableName();
    createTable($conn, $tableName, array("c1" => "varchar(32)"));

    if (!isColEncrypted()) {
        // Insert data
        $query = "INSERT INTO $tableName VALUES ('Salmon'),('Butterfish'),('Cod'),('NULL'),('Crab')";
        $stmt = $conn->query($query);
        $res[] = $stmt->rowCount();
        
        // Update data
        $query = "UPDATE $tableName SET c1='Salmon' WHERE c1='Cod'";
        $stmt = $conn->query($query);
        $res[] = $stmt->rowCount();
        
        // Update data
        $query = "UPDATE $tableName SET c1='Salmon' WHERE c1='NULL'";
        $stmt = $conn->query($query);
        $res[] = $stmt->rowCount();

        // Update data
        $query = "UPDATE $tableName SET c1='Salmon' WHERE c1='NO_NAME'";
        $stmt = $conn->query($query);
        $res[] = $stmt->rowCount();

        // Update data
        $query = "UPDATE $tableName SET c1='N/A'";
        $stmt = $conn->query($query);
        $res[] = $stmt->rowCount();
        
        unset($stmt);
    } else {
        // Insert data
        // bind parameter does not work with inserting multiple rows in one SQL command, thus need to insert each row separately
        $query = "INSERT INTO $tableName VALUES (?)";
        $stmt = $conn->prepare($query);
        $params = array("Salmon", "Butterfish", "Cod", "NULL", "Crab");
        foreach ($params as $param) {
            $stmt->execute(array($param));
        }
        $res[] = count($params);
        
        // Update data
        $query = "UPDATE $tableName SET c1=? WHERE c1=?";
        $stmt = $conn->prepare($query);
        $stmt->execute(array("Salmon", "Cod"));
        $res[] = $stmt->rowCount();
        
        // Update data
        $stmt->execute(array("Salmon", "NULL"));
        $res[] = $stmt->rowCount();
        
        // Update data
        $stmt->execute(array("Salmon", "NO_NAME"));
        $res[] = $stmt->rowCount();
        
        $query = "UPDATE $tableName SET c1=?";
        $stmt = $conn->prepare($query);
        $stmt->execute(array("N/A"));
        $res[] = $stmt->rowCount();
        
        unset($stmt);
    }

    print_r($res);

    dropTable($conn, $tableName);
    unset($conn);
    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Array
(
    [0] => 5
    [1] => 1
    [2] => 1
    [3] => 0
    [4] => 5
)
Done
