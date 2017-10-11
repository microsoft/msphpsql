--TEST--
fetch multiple result sets with MARS on and then off
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function NestedQuery_Mars($on)
{
    $tableName = getTableName();

    $conn = connect("MultipleActiveResultSets=$on");

    createTable($conn, $tableName, array("c1_int" => "int", "c2_varchar" => "varchar(20)"));

    insertRow($conn, $tableName, array("c1_int" => 1, "c2_varchar" => "Dummy value 1"));
    insertRow($conn, $tableName, array("c1_int" => 2, "c2_varchar" => "Dummy value 2"));

    if (!isColEncrypted()) {
        $query = "SELECT * FROM $tableName ORDER BY [c1_int]";
    } else {
        // ORDER BY is not support for encrypted columns
        $query = "SELECT * FROM $tableName";
    }
    $stmt = $conn->query($query);
    $numRows = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $numRows++;
    }

    if ($numRows !== 2) {
        echo "Number of rows is unexpected!\n";
    }
    unset($stmt);

    // more than one active results
    $stmt1 = $conn->query($query);
    $stmt2 = $conn->prepare($query);
    $stmt2->execute();

    echo "\nNumber of columns in First set: " . $stmt2->columnCount() . "\n";
    while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }

    echo "\nNumber of columns in Second set: " . $stmt1->columnCount() . "\n\n";
    while ($row = $stmt2->fetch(PDO::FETCH_OBJ)) {
        print_r($row);
    }

    dropTable($conn, $tableName);
    unset($stmt1);
    unset($stmt2);
    unset($conn);
}

echo "Starting test...\n";
try {
    NestedQuery_Mars(true);
    NestedQuery_Mars(false);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";
?>
--EXPECT--
Starting test...

Number of columns in First set: 2
Array
(
    [c1_int] => 1
    [c2_varchar] => Dummy value 1
)
Array
(
    [c1_int] => 2
    [c2_varchar] => Dummy value 2
)

Number of columns in Second set: 2

stdClass Object
(
    [c1_int] => 1
    [c2_varchar] => Dummy value 1
)
stdClass Object
(
    [c1_int] => 2
    [c2_varchar] => Dummy value 2
)
SQLSTATE[IMSSP]: The connection cannot process this operation because there is a statement with pending results.  To make the connection available for other queries, either fetch all results or cancel or free the statement.  For more information, see the product documentation about the MultipleActiveResultSets connection option.
Done
