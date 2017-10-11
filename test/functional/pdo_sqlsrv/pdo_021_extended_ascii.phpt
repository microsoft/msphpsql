--TEST--
Bind parameters VARCHAR(n) extended ASCII
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    $conn = connect();

    // Create table
    $tableName = 'extendedAscii';
    createTable( $conn, $tableName, array("code" => "char(2)", "city" => "varchar(32)"));

    // Insert data using bind parameters
    $sql = "INSERT INTO $tableName VALUES (?,?)";

    // First row 
    $stmt = $conn->prepare($sql);
    $params = array("FI","Järvenpää");
    $stmt->execute($params);

    // Second row
    $params = array("DE","München");
    $stmt->execute($params);

    // Query, fetch
    $data = selectAll($conn, $tableName);

    // Print out
    foreach ($data as $a)
    echo $a[0] . "|" . $a[1] . "\n";

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
FI|Järvenpää
DE|München
Done
