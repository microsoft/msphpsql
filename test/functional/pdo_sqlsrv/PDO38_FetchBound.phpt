--TEST--
PDO Fetch Bound Test
--DESCRIPTION--
Verification for "PDOStatenent::fetch(PDO::FETCH_BOUND)".
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
    $tableName = "php_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY"), "val" => "varchar(10)", "val2" => "varchar(16)"));
    $data = array(array('10', 'Abc', 'zxy'),
                  array('20', 'Def', 'wvu'),
                  array('30', 'Ghi', 'tsr'),
                  array('40', 'Jkl', 'qpo'),
                  array('50', 'Mno', 'nml'),
                  array('60', 'Pqr', 'kji'));

    // Insert using question mark placeholders
    $stmt1 = $conn1->prepare("INSERT INTO [$tableName] VALUES(?, ?, ?)");
    foreach ($data as $row) {
        $stmt1->execute($row);
    }
    unset($stmt1);

    // Count inserted rows
    $stmt2 = $conn1->prepare("SELECT COUNT(id) FROM [$tableName]");
    $stmt2->execute();
    $num = $stmt2->fetchColumn();
    echo "There are $num rows in the table.\n";
    $stmt2->closeCursor();

    // Insert using named parameters
    $stmt1 = $conn1->prepare("INSERT INTO [$tableName] VALUES(:first, :second, :third)");
    foreach ($data as $row) {
        $stmt1->execute(array(':first'=>($row[0] + 5), ':second'=>$row[1], ':third'=>$row[2]));
    }
    unset($stmt1);

    $stmt2->execute();
    $num = $stmt2->fetchColumn();
    echo "There are $num rows in the table.\n";

    createTable($conn1, $tableName, array(new ColumnMeta("int", "idx", "NOT NULL PRIMARY KEY", "none"), "txt" => "varchar(20)"));
    insertRow($conn1, $tableName, array("idx" => 0, "txt" => 'String0'));
    insertRow($conn1, $tableName, array("idx" => 1, "txt" => 'String1'));
    insertRow($conn1, $tableName, array("idx" => 2, "txt" => 'String2'));

    // Testing with prepared query
    $stmt1 = $conn1->prepare("SELECT COUNT(idx) FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchColumn());
    unset($stmt1);

    $stmt1 = $conn1->prepare("SELECT idx, txt FROM [$tableName] ORDER BY idx");    
    $stmt1->execute();
    $data = $stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);
    var_dump($data);

    echo "===WHILE===\n";

    $stmt1->bindColumn('idx', $idx);
    $stmt1->bindColumn('txt', $txt);
    $stmt1->execute();
    while ($stmt1->fetch(PDO::FETCH_BOUND)) {
        var_dump(array($idx=>$txt));
    }

    echo "===ALONE===\n";

    $stmt2 = $conn1->prepare("SELECT txt FROM [$tableName] WHERE idx=:inp");
    $stmt2->bindParam(':inp', $idx);    // by foreign name

    $stmt3 = $conn1->prepare("SELECT idx FROM [$tableName] WHERE txt=:txt");
    $stmt3->bindParam(':txt', $txt);    // using same name

    foreach ($data as $idx => $txt) {
        var_dump(array($idx=>$txt));

        var_dump($stmt2->execute());
        if ($idx == 0) {   // bindColumn()s after execute() has been called at least once
            $stmt2->bindColumn('txt', $col);
        }
        var_dump($stmt2->fetch(PDO::FETCH_BOUND));
        $stmt2->closeCursor();

        var_dump($stmt3->execute());
        if ($idx == 0) {   // bindColumn()s after execute() has been called at least once
            $stmt3->bindColumn('idx', $idxCol);
        }
        var_dump($stmt3->fetch(PDO::FETCH_BOUND));
        $stmt3->closeCursor();

        var_dump(array($idxCol=>$col));
    }

    echo "===REBIND/SAME===\n";

    $stmt3->bindColumn('idx', $col);
    foreach ($data as $idx => $txt) {
        var_dump(array($idx=>$txt));
        var_dump($stmt2->execute());
        var_dump($stmt2->fetch(PDO::FETCH_BOUND));
        $stmt2->closeCursor();

        var_dump($col);
        var_dump($stmt3->execute());
        var_dump($stmt3->fetch(PDO::FETCH_BOUND));
        $stmt3->closeCursor();
        var_dump($col);
    }

    echo "===REBIND/CONFLICT===\n";

    $stmt1->bindColumn('idx', $col);
    $stmt1->bindColumn('txt', $col);
    $stmt1->execute();
    while ($stmt1->fetch(PDO::FETCH_BOUND)) {
        var_dump($col);
    }

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($stmt2);
    unset($stmt3);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
There are 6 rows in the table.
There are 12 rows in the table.
string(1) "3"
array(3) {
  [0]=>
  string(7) "String0"
  [1]=>
  string(7) "String1"
  [2]=>
  string(7) "String2"
}
===WHILE===
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
===ALONE===
array(1) {
  [0]=>
  string(7) "String0"
}
bool(true)
bool(true)
bool(true)
bool(true)
array(1) {
  [0]=>
  string(7) "String0"
}
array(1) {
  [1]=>
  string(7) "String1"
}
bool(true)
bool(true)
bool(true)
bool(true)
array(1) {
  [1]=>
  string(7) "String1"
}
array(1) {
  [2]=>
  string(7) "String2"
}
bool(true)
bool(true)
bool(true)
bool(true)
array(1) {
  [2]=>
  string(7) "String2"
}
===REBIND/SAME===
array(1) {
  [0]=>
  string(7) "String0"
}
bool(true)
bool(true)
string(7) "String0"
bool(true)
bool(true)
string(1) "0"
array(1) {
  [1]=>
  string(7) "String1"
}
bool(true)
bool(true)
string(7) "String1"
bool(true)
bool(true)
string(1) "1"
array(1) {
  [2]=>
  string(7) "String2"
}
bool(true)
bool(true)
string(7) "String2"
bool(true)
bool(true)
string(1) "2"
===REBIND/CONFLICT===
string(7) "String0"
string(7) "String1"
string(7) "String2"
