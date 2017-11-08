--TEST--
test transaction rollback and commit
--DESCRIPTION--
starts a transaction, delete rows and rollback the transaction; starts a transaction, delete rows and commit
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function deleteRows($conn, $tbname)
{
    try {
        if (!isColEncrypted()) {
            $rows = $conn->exec("DELETE FROM $tbname WHERE col1 = 'a'");
        } else {
            // needs to find parameter for encrypted columns
            $sql = "DELETE FROM $tbname WHERE col1 = ?";
            $stmt = $conn->prepare($sql);
            $col1 = "a";
            $stmt->execute(array($col1));
            $rows = $stmt->rowCount();
        }
        return $rows;
    } catch (PDOException $e) {
        var_dump($e->errorInfo);
    }
}

try {
    $conn = connect();

    $tbname = "Table1";
    createTable($conn, $tbname, array("col1" => "char(1)", "col2" => "char(1)"));

    insertRow($conn, $tbname, array("col1" => "a", "col2" => "b"));
    insertRow($conn, $tbname, array("col1" => "a", "col2" => "c"));

    //revert the inserts but roll back
    $conn->beginTransaction();
    $rows = deleteRows($conn, $tbname);
    $conn->rollback();
    $stmt = $conn->query("SELECT * FROM $tbname");

    // Table1 should still have 2 rows since delete was rolled back
    if (count($stmt->fetchAll()) == 2) {
        echo "Transaction rolled back successfully\n";
    } else {
        echo "Transaction failed to roll back\n";
    }

    //revert the inserts then commit
    $conn->beginTransaction();
    $rows = deleteRows($conn, $tbname);
    $conn->commit();
    echo $rows." rows affected\n";

    $stmt = $conn->query("SELECT * FROM $tbname");
    if (count($stmt->fetchAll()) == 0) {
        echo "Transaction committed successfully\n";
    } else {
        echo "Transaction failed to commit\n";
    }

    dropTable($conn, $tbname);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Transaction rolled back successfully
2 rows affected
Transaction committed successfully
