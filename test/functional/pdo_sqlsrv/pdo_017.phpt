--TEST--
Fetch string with new line and tab characters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    $conn = connect();

    // Create table
    $tableName = 'pdo_017';
    createTable($conn, $tableName, array("c1" => "varchar(32)", "c2" => "char(32)", "c3" => "nvarchar(32)", "c4" => "nchar(32)"));

    // Bind parameters and insert data
    $sql = "INSERT INTO $tableName VALUES (:val1, :val2, :val3, :val4)";
    $value = "I USE\nMSPHPSQL\tDRIVERS WITH PHP7";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':val1', $value);
    $stmt->bindParam(':val2', $value);
    $stmt->bindParam(':val3', $value);
    $stmt->bindParam(':val4', $value);
    $stmt->execute();

    // Get data
    if (!isColEncrypted()) {
        $sql = "SELECT UPPER(c1) AS VARCHAR, UPPER(c2) AS CHAR, 
                UPPER(c3) AS NVARCHAR, UPPER(c4) AS NCHAR FROM $tableName";
        $stmt = $conn->query($sql);
    } else {
        // upper function is not supported in Always Encrypted
        $sql = "SELECT c1 AS VARCHAR, c2 AS CHAR, 
                c3 AS NVARCHAR, c4 AS NCHAR FROM $tableName";
        $stmt = $conn->query($sql);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
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
array(4) {
  ["VARCHAR"]=>
  string(32) "I USE
MSPHPSQL	DRIVERS WITH PHP7"
  ["CHAR"]=>
  string(32) "I USE
MSPHPSQL	DRIVERS WITH PHP7"
  ["NVARCHAR"]=>
  string(32) "I USE
MSPHPSQL	DRIVERS WITH PHP7"
  ["NCHAR"]=>
  string(32) "I USE
MSPHPSQL	DRIVERS WITH PHP7"
}
Done
