--TEST--
Test PDO::__Construct connection option TransactionIsolation
--SKIPIF--

--FILE--
<?php
function Connect($value) {
    require("autonomous_setup.php");
    $database = "tempdb";
    $dsn = "sqlsrv:Server = $serverName;" .
           "Database = $database" ;//.
           "TransactionIsolation = $value";
    $conn = new PDO( $dsn, $username, $password );
    $conn = NULL;
}

// TEST BEGIN
try {
Connect("PDO::SQLSRV_TXN_READ_UNCOMMITTED");
Connect("PDO::SQLSRV_TXN_READ_COMMITTED");
Connect("PDO::SQLSRV_TXN_REPEATABLE_READ");
Connect("PDO::SQLSRV_TXN_SNAPSHOT");
Connect("PDO::SQLSRV_TXN_SERIALIZABLE");
  
  echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}
?> 

--EXPECT--

Test Successful