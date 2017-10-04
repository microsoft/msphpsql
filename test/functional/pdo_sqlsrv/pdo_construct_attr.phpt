--TEST--
Test PDO::__Construct by passing different connection attributes
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $attr = array( PDO::SQLSRV_ATTR_ENCODING => 3,
                   PDO::ATTR_CASE => 2,
                   PDO::ATTR_PREFETCH => false,
                   PDO::ATTR_TIMEOUT => 35,
                   PDO::ATTR_STRINGIFY_FETCHES => true,
                   PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
                   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                   PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
                   PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE => 10240,
                   PDO::SQLSRV_ATTR_DIRECT_QUERY => true );

    $conn = connect("", $attr);

    $stmt = $conn->prepare("SELECT 1");
    $stmt->execute();

    // fetch result, which should be stringified since ATTR_STRINGIFY_FETCHES is on
    var_dump($stmt->fetch(PDO::FETCH_ASSOC));

    unset($stmt);
    unset($conn);

    echo "Test Successful\n";
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
array(1) {
  [""]=>
  string(1) "1"
}
Test Successful
