--TEST--
GitHub issue 1310 - bind null field as varchar(max) if not binary
--DESCRIPTION--
The test shows null fields are no longer bound as char(1) if not binary such that it solves both issues 1310 and 1102.
Note that this test does not connect with AE enabled because SQLDescribeParam() does not work with these queries.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

try {
    $conn = new PDO("sqlsrv:server=$server; Database = $databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Issue 1310
    $query = "SELECT CAST(ISNULL(:K, -1) AS INT) AS K";
    $k = null;

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':K', $k, PDO::PARAM_NULL);
    $stmt->execute();

    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($row);

    $stmt->bindParam(':K', $k, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetchAll(PDO::FETCH_NUM);
    var_dump($row);

    // Issue 1102
    $query = "DECLARE @d DATETIME = ISNULL(:K, GETDATE()); SELECT @d AS D;";
    $k = null;

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':K', $k, PDO::PARAM_NULL);
    $stmt->execute();

    $row = $stmt->fetchAll(PDO::FETCH_NUM);
    var_dump($row);

    $stmt->bindParam(':K', $k, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($row);

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage();
}

?>
--EXPECTREGEX--
array\(1\) {
  \[0\]=>
  array\(1\) {
    \["K"\]=>
    string\(2\) "-1"
  }
}
array\(1\) {
  \[0\]=>
  array\(1\) {
    \[0\]=>
    string\(2\) "-1"
  }
}
array\(1\) {
  \[0\]=>
  array\(1\) {
    \[0\]=>
    string\(23\) "20[0-9]{2}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:00.000"
  }
}
array\(1\) {
  \[0\]=>
  array\(1\) {
    \["D"\]=>
    string\(23\) "20[0-9]{2}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:00.000"
  }
}
Done

