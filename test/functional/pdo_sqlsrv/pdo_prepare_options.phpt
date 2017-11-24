--TEST--
Test PDO::prepare by passing in attributes
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

try {
    class CustomPDOStatement extends PDOStatement
    {
        protected function __construct()
        {
        }
    }

    $conn = connect();

    $prep_attr = array(PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
                       PDO::ATTR_STATEMENT_CLASS => array('CustomPDOStatement', array()),
                       PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
                       PDO::ATTR_EMULATE_PREPARES => false,
                       PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true);
    $stmt = $conn->prepare("SELECT 1", $prep_attr);

    echo "Test Successful";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
Test Successful
