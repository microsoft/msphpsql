--TEST--
Insert with quoted parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    $conn = connect();

    $param = 'a \' g';
    $param2 = $conn->quote($param);

    // Create a temporary table
    $tableName = getTableName();
    $stmt = createTable($conn, $tableName, array("col1" => "varchar(10)", "col2" => "varchar(20)"));

    // Insert data
    if (!isColEncrypted()) {
        $query = "INSERT INTO $tableName VALUES(?, '1')";
        $stmt = $conn->prepare($query);
        $stmt->execute(array($param));
    } else {
        insertRow($conn, $tableName, array("col1" => $param, "col2" => "1"), "prepareExecuteBind");
    }

    // Insert data
    insertRow($conn, $tableName, array("col1" => $param, "col2" => $param2), "prepareExecuteBind");

    // Query
    $query = "SELECT * FROM $tableName";
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
       print_r($row['col1'] ." was inserted\n");
    }

    // Revert the inserts
    $query = "delete from $tableName where col1 = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute(array($param));

    //free the statement and connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
a ' g was inserted
a ' g was inserted