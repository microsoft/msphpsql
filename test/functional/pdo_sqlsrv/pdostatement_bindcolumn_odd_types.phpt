--TEST--
Test the bindColumn method using PDO::PARAM_NULL and PDO::PARAM_STMT
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $tbname = "table1";
    createTable($conn, $tbname, array("IntCol" => "int", "CharCol" => "nvarchar(20)"));
    insertRow($conn, $tbname, array("IntCol" => 10, "CharCol" => "ten"));

    $stmt = $conn->prepare("SELECT IntCol FROM table1");
    $stmt->execute();

    // PARAM_NULL returns null
    $stmt->bindColumn('IntCol', $intCol, PDO::PARAM_NULL);
    $row = $stmt->fetch(PDO::FETCH_BOUND);
    if ($intCol == null) {
        echo "intCol is NULL\n";
    } else {
        echo "intCol should have been NULL\n";
    }

    $stmt = $conn->prepare("SELECT CharCol FROM table1");
    $stmt->execute();

    // PARAM_STMT is not supported and should throw an exception
    $stmt->bindColumn('CharCol', $charCol, PDO::PARAM_STMT);
    $row = $stmt->fetch(PDO::FETCH_BOUND);
    echo "PARAM_STMT should have thrown an exception\n";
} catch (PDOException $e) {
    print_r($e->errorInfo[2]);
    echo "\n";
}

?>
--EXPECT--
intCol is NULL
PDO::PARAM_STMT is not a supported parameter type.
