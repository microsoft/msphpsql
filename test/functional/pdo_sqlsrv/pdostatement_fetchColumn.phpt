--TEST--
Test the fetchColumn() method.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createAndInsertTableMainTypes($db, $tbname);

    $stmt = $db->query("Select * from $tbname");

    // Fetch the first column from the next row in resultset. (This would be first row since this is a first call to fetchcol)
    $result = $stmt->fetchColumn();
    var_dump($result);

    // Fetch the second column from the next row. (This would be second row since this is a second call to fetchcol).
    $result = $stmt->fetchColumn(1);
    var_dump($result);

    // Test false is returned when there are no more rows.
    $result = $stmt->fetchColumn(1);
    var_dump($result);

    dropTable($db, $tbname);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}


?>
--EXPECT--
string(1) "1"
string(10) "STRINGCOL2"
bool(false)
