--TEST--
prepare with cursor buffered and fetch from numeric columns.
--DESCRIPTION--
Uses buffered cursor to fetch from float, int, and decimal columns that have positive, negative and zero value.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $sample = 1234567890.1234;
    $sample1 = -1234567890.1234;
    $sample2 = 1;
    $sample3 = -1;
    $sample4 = 0.5;
    $sample5 = -0.55;

    $tbname = "TESTTABLE";
    createTable($conn, $tbname, array("a" => "float(53)", "neg_a" => "float(53)",
                                      "b" => "int", "neg_b" => "int",
                                      "c" => "decimal(16,6)", "neg_c" => "decimal(16,6)",
                                      "zero" => "int", "zerof" => "float(53)", "zerod" => "decimal(16,6)"));

    if (!isColEncrypted()) {
        $query = "INSERT INTO $tbname VALUES(:p0, :p1, :p2, :p3, :p4, :p5, 0, 0, 0)";
        $stmt = $conn->prepare($query);
    } else {
        // Encrypted columns require all inputs to be bound
        $query = "INSERT INTO $tbname VALUES(:p0, :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8)";
        $stmt = $conn->prepare($query);
        $zero = 0;
        $stmt->bindValue(':p6', $zero, PDO::PARAM_INT);
        $stmt->bindValue(':p7', $zero, PDO::PARAM_INT);
        $stmt->bindValue(':p8', $zero, PDO::PARAM_INT);
    }
    $stmt->bindValue(':p0', $sample, PDO::PARAM_INT);
    $stmt->bindValue(':p1', $sample1, PDO::PARAM_INT);
    $stmt->bindValue(':p2', $sample2, PDO::PARAM_INT);
    $stmt->bindValue(':p3', $sample3, PDO::PARAM_INT);
    $stmt->bindValue(':p4', $sample4, PDO::PARAM_INT);
    $stmt->bindValue(':p5', $sample5, PDO::PARAM_INT);
    $stmt->execute();

    $query = "SELECT TOP 1 * FROM $tbname";

    //prepare with no buffered cursor
print "\nno buffered cursor, stringify off, fetch_numeric off\n"; //stringify and fetch_numeric is off by default
$stmt = $conn->prepare($query);
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    print "\nno buffered cursor, stringify off, fetch_numeric on\n";
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    print "\nno buffered cursor, stringify on, fetch_numeric on\n";
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    print "\nno buffered cursor, stringify on, fetch_numeric off\n";
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    //prepare with client buffered cursor
    print "\nbuffered cursor, stringify off, fetch_numeric off\n";
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    print "\nbuffered cursor, stringify off, fetch_numeric on\n";
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
    $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    print "\nbuffered cursor, stringify on, fetch_numeric on\n";
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
    $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    print "\nbuffered cursor, stringify on, fetch_numeric off\n";
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($value);

    dropTable($conn, $tbname);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
no buffered cursor, stringify off, fetch_numeric off
array(9) {
  [0]=>
  string(15) "1234567890.1234"
  [1]=>
  string(16) "-1234567890.1234"
  [2]=>
  string(1) "1"
  [3]=>
  string(2) "-1"
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  string(1) "0"
  [7]=>
  string(3) "0.0"
  [8]=>
  string(7) ".000000"
}

no buffered cursor, stringify off, fetch_numeric on
array(9) {
  [0]=>
  float(1234567890.1234)
  [1]=>
  float(-1234567890.1234)
  [2]=>
  int(1)
  [3]=>
  int(-1)
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  int(0)
  [7]=>
  float(0)
  [8]=>
  string(7) ".000000"
}

no buffered cursor, stringify on, fetch_numeric on
array(9) {
  [0]=>
  string(15) "1234567890.1234"
  [1]=>
  string(16) "-1234567890.1234"
  [2]=>
  string(1) "1"
  [3]=>
  string(2) "-1"
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  string(1) "0"
  [7]=>
  string(1) "0"
  [8]=>
  string(7) ".000000"
}

no buffered cursor, stringify on, fetch_numeric off
array(9) {
  [0]=>
  string(15) "1234567890.1234"
  [1]=>
  string(16) "-1234567890.1234"
  [2]=>
  string(1) "1"
  [3]=>
  string(2) "-1"
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  string(1) "0"
  [7]=>
  string(3) "0.0"
  [8]=>
  string(7) ".000000"
}

buffered cursor, stringify off, fetch_numeric off
array(9) {
  [0]=>
  string(15) "1234567890.1234"
  [1]=>
  string(16) "-1234567890.1234"
  [2]=>
  string(1) "1"
  [3]=>
  string(2) "-1"
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  string(1) "0"
  [7]=>
  string(1) "0"
  [8]=>
  string(7) ".000000"
}

buffered cursor, stringify off, fetch_numeric on
array(9) {
  [0]=>
  float(1234567890.1234)
  [1]=>
  float(-1234567890.1234)
  [2]=>
  int(1)
  [3]=>
  int(-1)
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  int(0)
  [7]=>
  float(0)
  [8]=>
  string(7) ".000000"
}

buffered cursor, stringify on, fetch_numeric on
array(9) {
  [0]=>
  string(15) "1234567890.1234"
  [1]=>
  string(16) "-1234567890.1234"
  [2]=>
  string(1) "1"
  [3]=>
  string(2) "-1"
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  string(1) "0"
  [7]=>
  string(1) "0"
  [8]=>
  string(7) ".000000"
}

buffered cursor, stringify on, fetch_numeric off
array(9) {
  [0]=>
  string(15) "1234567890.1234"
  [1]=>
  string(16) "-1234567890.1234"
  [2]=>
  string(1) "1"
  [3]=>
  string(2) "-1"
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  string(1) "0"
  [7]=>
  string(1) "0"
  [8]=>
  string(7) ".000000"
}
