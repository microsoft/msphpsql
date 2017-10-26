--TEST--
Bind integer parameters; allow fetch numeric types.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    /* Sample numbers MIN_INT, MAX_INT */
    $sample = array(-2**31, 2**31-1);

    /* Connect */
    $conn = connect('', array(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => TRUE));

    // Create table
    $tableName = 'testPDO016';
    createTable($conn, $tableName, array("c1" => "int", "c2" => "int"));

    // Insert data using bind parameters
    $sql = "INSERT INTO $tableName VALUES (:num1, :num2)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':num1', $sample[0], PDO::PARAM_INT);
    $stmt->bindParam(':num2', $sample[1], PDO::PARAM_INT);
    $stmt->execute();

    // Fetch, get data
    $sql = "SELECT * FROM $tableName";
    $stmt = $conn->query($sql);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($row);

    // Close connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);

    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
array(2) {
  [0]=>
  int(-2147483648)
  [1]=>
  int(2147483647)
}
Done
