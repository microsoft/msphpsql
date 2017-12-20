--TEST--
PDO Bind Param Test
--DESCRIPTION--
Verification for "PDOStatement::bindParam()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn1 = connect();

    // Prepare test table
    $dataCols = "id, name";
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array("id" => "int", "name" => "varchar(20)"));
    $conn1->exec("CREATE CLUSTERED INDEX [idx_test_int] ON $tableName (id)");

    // Insert test data
    if (isColEncrypted()) {
        $stmt1 = $conn1->prepare("INSERT INTO [$tableName] ($dataCols) VALUES(:id, :name)");
        $id = 0;
        $stmt1->bindParam(':id', $id);
    } else {
        $stmt1 = $conn1->prepare("INSERT INTO [$tableName] ($dataCols) VALUES(0, :name)");
    }
    $name = null;
    $before_bind = $name;
    $stmt1->bindParam(':name', $name);

    // Check that bindParam does not modify parameter
    if ($name !== $before_bind) {
        echo "bind: fail\n";
    } else {
        echo "bind: success\n";
    }

    var_dump($stmt1->execute());
    unset($stmt1);

    // Retrieve test data
    if (isColEncrypted()) {
        $stmt1 = $conn1->prepare("SELECT name FROM [$tableName] WHERE id = ?");
        $id = 0;
        $stmt1->bindParam(1, $id);
        $stmt1->execute();
    } else {
        $stmt1 = $conn1->query("SELECT name FROM [$tableName] WHERE id = 0");
    }
    var_dump($stmt1->fetchColumn());

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
bind: success
bool(true)
NULL
