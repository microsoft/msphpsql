--TEST--
Test errorInfo when prepare with and without emulate prepare
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkParameterWarnings($operation, $methods)
{
    $warnings = array();
    set_error_handler(function ($severity, $message) use (&$warnings) {
        $warnings[] = $message;
        return true;
    }, E_WARNING);
    try {
        $operation();
    } finally {
        restore_error_handler();
    }

    // PHP 8.6 honors ERRMODE_SILENT for PDO implementation errors.
    $expectedCount = PHP_VERSION_ID < 80600 ? 1 : 0;
    $expectedMessages = array();
    foreach ($methods as $method) {
        $expectedMessages[] = "PDOStatement::$method(): SQLSTATE[HY093]: Invalid parameter number: parameter was not defined";
    }
    $valid = count($warnings) === $expectedCount;
    foreach ($warnings as $warning) {
        $valid = in_array($warning, $expectedMessages, true) && $valid;
    }
    if (!$valid) {
        echo "Expected $expectedCount matching HY093 warning(s); received:\n";
        var_dump($warnings);
    }
}

try {
    // connection with and without column encryption returns different warning since column encryption cannot use emulate prepare
    // turn ERRMODE to silent to compare the errorCode in the test
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    if ($conn->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_SILENT) {
        echo "Connection error mode should be ERRMODE_SILENT.\n";
    }

    //drop, create and insert
    $tbname = "test_table";
    createTable($conn, $tbname, array("c1" => "int", "c2" => "int"));

    insertRow($conn, $tbname, array( "c1" => 1, "c2" => 10 ));
    insertRow($conn, $tbname, array( "c1" => 2, "c2" => 20 ));

    echo "\n****testing with emulate prepare****\n";
    // Do not support emulate prepare with Always Encrypted
    if (!isAEConnected()) {
        $stmt = $conn->prepare("SELECT c2 FROM $tbname WHERE c1= :int", array(PDO::ATTR_EMULATE_PREPARES => true));
    } else {
        $stmt = $conn->prepare("SELECT c2 FROM $tbname WHERE c1= :int");
    }

    $int_col = 1;
    //bind param with the wrong parameter name to test for errorInfo
    checkParameterWarnings(function () use ($stmt, &$int_col) {
        $stmt->bindParam(':in', $int_col);
        $stmt->execute();
    }, array("bindParam", "execute"));

    $stmt_error = $stmt->errorInfo();
    if (!isAEConnected()) {
        if ($stmt_error[0] != "HY093") {
            echo "SQLSTATE should be HY093 when Emulate Prepare is true.\n";
            print_r($stmt_error);
        }
    } else {
        if ($stmt_error[0] != "07002") {
            echo "SQLSTATE should be 07002 for syntax error in a parameterized query.\n";
            print_r($stmt_error);
        }
    }

    $conn_error = $conn->errorInfo();
    if ($conn_error[0] != "00000") {
        echo "Connection error SQLSTATE should be 00000.\n";
        print_r($conn_error);
    }

    echo "\n****testing without emulate prepare****\n";
    $stmt2 = $conn->prepare("SELECT c2 FROM $tbname WHERE c1= :int", array(PDO::ATTR_EMULATE_PREPARES => false));

    $int_col = 2;
    //bind param with the wrong parameter name to test for errorInfo
    checkParameterWarnings(function () use ($stmt2, &$int_col) {
        $stmt2->bindParam(':it', $int_col);
        $stmt2->execute();
    }, array("bindParam"));

    $stmt_error = $stmt2->errorInfo();
    if ($stmt_error[0] != "07002") {
        echo "SQLSTATE should be 07002 for syntax error in a parameterized query.\n";
        print_r($stmt_error);
    }

    $conn_error = $conn->errorInfo();
    if ($conn_error[0] != "00000") {
        echo "Connection error SQLSTATE should be 00000.\n";
        print_r($conn_error);
    }

    dropTable($conn, $tbname);
    unset($stmt);
    unset($stmt2);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
****testing with emulate prepare****

****testing without emulate prepare****
