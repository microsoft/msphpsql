--TEST--
PDO Test for PDO::errorInfo()
--DESCRIPTION--
Verification of PDO::errorInfo()
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn1 = connect("", array(), PDO::ERRMODE_SILENT);
    checkError(1, $conn1, '00000');

    // Prepare test table
    $table1 = "Table1";
    $table2 = "Table2";
    createTable($conn1, $table1, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY"), "label" => "varchar(10)"));

    // Check errors when executing SELECT queries
    $stmt1 = $conn1->prepare("SELECT id, label FROM [$table1]");
    checkError(2, $conn1);
    checkError(3, $stmt1);
    $stmt1->execute();
    $stmt2 = &$stmt1;
    checkError(4, $stmt1);
    $stmt1->closeCursor();

    dropTable($conn1, $table1);
    @$stmt1->execute();
    checkError(5, $conn1);
    checkError(6, $stmt1, '42S02');
    checkError(7, $stmt2, '42S02');
    $stmt1->closeCursor();

    dropTable($conn1, $table2);
    $conn2 = &$conn1;
    @$conn1->query("SELECT id, label FROM [$table2]");
    checkError(8, $conn1, '42S02');
    checkError(9, $conn2, '42S02');

    createTable($conn1, $table1, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY"), "label" => "varchar(10)"));

    $stmt1 = $conn1->query("SELECT id, label FROM [$table1]");
    checkError(10, $conn1);
    checkError(11, $stmt1);
    $stmt1->closeCursor();

    @$conn1->query("SELECT id, label FROM [$table2]");
    checkError(12, $conn1, '42S02');
    checkError(13, $conn2, '42S02');
    checkError(14, $stmt1);

    // Cleanup
    dropTable($conn1, $table1);
    unset($stmt1);
    unset($conn1);
    echo "Done\n";
} catch (Exception $e) {
    echo $e->getMessage();
}


function checkError($offset, &$obj, $expected = '00000')
{
    $info = $obj->errorInfo();
    $code = $info[0];

    if (($code != $expected) && (($expected != '00000') || ($code != ''))) {
        printf("[%03d] Expecting error code '%s' got code '%s'\n", $offset, $expected, $code);
    }
    if ($expected != '00000') {
        if (!isset($info[1]) || ($info[1] == '')) {
            printf("[%03d] Driver-specific error code not set\n", $offset);
        }
        if (!isset($info[2]) || ($info[2] == '')) {
            printf("[%03d] Driver-specific error message not set\n", $offset);
        }
    }
}
?>
--EXPECT--
Done
