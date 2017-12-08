--TEST--
PDO Bind Value Test
--DESCRIPTION--
Verification for "PDOStatement::bindValue()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');

try {
    $conn1 = connect();

    // Prepare test table
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY"), "val1" => "varchar(10)", "val2" => "varchar(10)", "val3" => "varchar(10)"));
    $data = array("one", "two", "three");

    // Insert test data
    $i = 1;
    if (!isColEncrypted()) {
        $stmt1 = $conn1->prepare("INSERT INTO [$tableName] VALUES(1, ?, ?, ?)");
    } else {
        $stmt1 = $conn1->prepare("INSERT INTO [$tableName] VALUES(?, ?, ?, ?)");
        $stmt1->bindValue(1, 1);
        $i++;
    }
    foreach ($data as $v) {
        $stmt1->bindValue($i, $v);
        $i++;
    }
    $stmt1->execute();
    unset($stmt1);

    // Retrieve test data
    $stmt1 = $conn1->prepare("SELECT * FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
array(1) {
  [0]=>
  array(4) {
    ["id"]=>
    string(1) "1"
    ["val1"]=>
    string(3) "one"
    ["val2"]=>
    string(3) "two"
    ["val3"]=>
    string(5) "three"
  }
}
