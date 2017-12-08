--TEST--
PDO Bind Column Test
--DESCRIPTION--
Verification for "PDOStatement::bindColumn".
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
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "idx", "NOT NULL PRIMARY KEY", "none"), "txt" => "varchar(20)"));
    insertRow($conn1, $tableName, array("idx" => 0, "txt" => "String0"));
    insertRow($conn1, $tableName, array("idx" => 1, "txt" => "String1"));
    insertRow($conn1, $tableName, array("idx" => 2, "txt" => "String2"));
    insertRow($conn1, $tableName, array("idx" => 3, "txt" => "String3"));

    // Testing with prepared query
    logInfo(1, "Testing fetchColumn() ...");
    $stmt1 = $conn1->query("SELECT COUNT(idx) FROM [$tableName]");
    var_dump($stmt1->fetchColumn());
    unset($stmt1);

    logInfo(2, "Testing fetchAll() ...");
    $stmt1 = $conn1->prepare("SELECT idx, txt FROM [$tableName] ORDER BY idx");
    $stmt1->execute();
    $data = $stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);
    var_dump($data);

    logInfo(3, "Testing bindColumn() ...");
    $stmt1->bindColumn('idx', $idx);
    $stmt1->bindColumn('txt', $txt);
    $stmt1->execute();
    while ($stmt1->fetch(PDO::FETCH_BOUND)) {
        var_dump(array($idx=>$txt));
    }

    logInfo(4, "Testing bindColumn() with data check ...");
    $id = null;
    $val = null;
    $data = array();
    $index = 0;
    if (!$stmt1->bindColumn(1, $id, PDO::PARAM_INT)) {
        logError(5, "Cannot bind integer column", $stmt1);
    }
    if (!$stmt1->bindColumn(2, $val, PDO::PARAM_STR)) {
        logError(5, "Cannot bind string column", $stmt1);
    }
    $stmt1->execute();
    while ($stmt1->fetch(PDO::FETCH_BOUND)) {
        $data[] = array('id' => $id, 'val' => $val);
        printf("id = %s (%s) / val = %s (%s)\n",
               var_export($id, true), gettype($id),
               var_export($val, true), gettype($val));
    }
    unset($stmt1);
    
    $stmt1 = $conn1->query("SELECT idx, txt FROM [$tableName] ORDER BY idx");
    while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        if ($row['idx'] != $data[$index]['id']) {
            logInfo(6, "Data corruption for integer column in row $index");
        }
        if ($row['txt'] != $data[$index]['val']) {
            logInfo(6, "Data corruption for string column in row $index");
        }
        $index++;
    }

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}

function logInfo($offset, $msg)
{
    printf("[%03d] %s\n", $offset, $msg);
}

function logError($offset, $msg, &$obj)
{
    printf("[%03d] %s: %s\n", $offset, $msg, $obj->errorCode);
}

?>
--EXPECT--
[001] Testing fetchColumn() ...
string(1) "4"
[002] Testing fetchAll() ...
array(4) {
  [0]=>
  string(7) "String0"
  [1]=>
  string(7) "String1"
  [2]=>
  string(7) "String2"
  [3]=>
  string(7) "String3"
}
[003] Testing bindColumn() ...
array(1) {
  [0]=>
  string(7) "String0"
}
array(1) {
  [1]=>
  string(7) "String1"
}
array(1) {
  [2]=>
  string(7) "String2"
}
array(1) {
  [3]=>
  string(7) "String3"
}
[004] Testing bindColumn() with data check ...
id = 0 (integer) / val = 'String0' (string)
id = 1 (integer) / val = 'String1' (string)
id = 2 (integer) / val = 'String2' (string)
id = 3 (integer) / val = 'String3' (string)
