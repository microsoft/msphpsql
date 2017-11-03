--TEST--
PDO Test for PDO::errorCode()
--DESCRIPTION--
Verification of PDO::errorCode()
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
try {
    $conn1 = connect();
    checkError(1, $conn1);

    // Prepare test table
    $table1 = "Table1";
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
    checkError(5, $conn1);

    // Cleanup
    unset($stmt);
    unset($conn);
    echo "Done\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

function checkError($offset, &$obj)
{
    $code = $obj->errorCode();
    $expected = '00000';
    if ($code != $expected && !empty($code)) {
        printf("[%03d] Expecting error code '%s' got code '%s'\n", $offset, $expected, $code);
    }
}
?>
--EXPECT--
Done
