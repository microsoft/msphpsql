--TEST--
Test Transactions.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

// commit
function test1($conn, $tbname)
{
    $conn->beginTransaction();
    insertRow($conn, $tbname, array("NCharCol" => "NCharCol3", "IntCol" => 3));
    $conn->commit();
    echo "Test 1 Passed\n";
}

// rollback
function test2($conn, $tbname)
{
    $conn->beginTransaction();
    insertRow($conn, $tbname, array("NCharCol" => "NCharCol3", "IntCol" => 3));
    $conn->rollBack();
    echo "Test 2 Passed\n";
}

// commit
function test3($conn, $tbname)
{
    $conn->beginTransaction();
    insertRow($conn, $tbname, array("NCharCol" => "NCharCol3", "IntCol" => 3));
    $conn->commit();
    echo "Test 3 Passed\n";
}

// Rollback twice. Verify that error is thrown
function test4($conn, $tbname)
{
    try {
        $conn->beginTransaction();
        insertRow($conn, $tbname, array("NCharCol" => "NCharCol3", "IntCol" => 3));
        $conn->rollBack();
        $conn->rollBack();
    } catch (PDOException $e) {
        echo "Test4: ". $e->getMessage() . "\n";
    }
}

// Commit twice Verify that error is thrown
function test5($conn, $tbname)
{
    try {
        $conn->beginTransaction();
        insertRow($conn, $tbname, array("NCharCol" => "NCharCol3", "IntCol" => 3));
        $conn->commit();
        $conn->commit();
    } catch (PDOException $e) {
        echo "Test5: ". $e->getMessage() . "\n";
    }
}

// begin transaction twice. Verify that error is thrown
function test6($conn)
{
    try {
        $conn->beginTransaction();
        $conn->beginTransaction();
    } catch (PDOException $e) {
        echo "Test6: ". $e->getMessage() . "\n";
    }
}

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createTableMainTypes($db, $tbname);
    test1($db, $tbname);
    test2($db, $tbname);
    test3($db, $tbname);
    test4($db, $tbname);
    test5($db, $tbname);
    test6($db);

    dropTable($db, $tbname);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
Test 1 Passed
Test 2 Passed
Test 3 Passed
Test4: There is no active transaction
Test5: There is no active transaction
Test6: There is already an active transaction
