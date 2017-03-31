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
Connect("READ_UNCOMMITTED");
Connect("READ_COMMITTED");
Connect("REPEATABLE_READ");
Connect("SNAPSHOT");
Connect("SERIALIZABLE");
  
  echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}
?> 

--EXPECT--

Test Successful