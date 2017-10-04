--TEST--
Test the PDO::exec() method.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $db = connect();

    $tbname = "tmp_table";
    $numRows = createTable($db, $tbname, array( new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY"), "val" => "varchar(10)"));
    var_dump($numRows);

    if (!isColEncrypted()) {
        $sql = "INSERT INTO $tbname VALUES(1, 'A')";
        $numRows = $db->exec($sql);
        var_dump($numRows);

        $sql = "INSERT INTO $tbname VALUES(2, 'B')";
        $numRows = $db->exec($sql);
        var_dump($numRows);

        $numRows = $db->exec("UPDATE $tbname SET val = 'X' WHERE id > 0");
        var_dump($numRows);
    } else {
        // cannot use exec for insertion and update with Always Encrypted
        $stmt = insertRow($db, $tbname, array( "id" => 1, "val" => "A" ));
        $numRows = $stmt->rowCount();
        var_dump($numRows);

        $stmt = insertRow($db, $tbname, array( "id" => 2, "val" => "B" ));
        $numRows = $stmt->rowCount();
        var_dump($numRows);

        // greater or less than operator is not support for encrypted columns
        $sql = "UPDATE $tbname SET val = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute(array( "X" ));
        $numRows = $stmt->rowCount();
        var_dump($numRows);
    }

    $numRows = $db->exec("DELETE FROM $tbname");
    var_dump($numRows);

    dropTable($db, $tbname);
    unset($stmt);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
int(0)
int(1)
int(1)
int(2)
int(2)
