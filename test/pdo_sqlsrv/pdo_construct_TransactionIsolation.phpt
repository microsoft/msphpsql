--TEST--
Test PDO::__Construct connection option TransactionIsolation
--SKIPIF--

--FILE--
<?php
function Connect($value) {
    require("autonomous_setup.php");
    $database = "tempdb";
    $dsn = "sqlsrv:Server = $serverName;" .
           "Database = $database;" .
           "TransactionIsolation = $value";
    $conn = new PDO( $dsn, $username, $password );
    $conn = NULL;
    echo "Test Successful\n";
}

// TEST BEGIN
try {
    Connect("READ_UNCOMMITTED");
    Connect("READ_COMMITTED");
    Connect("REPEATABLE_READ");
    Connect("SNAPSHOT");
    Connect("SERIALIZABLE");
    Connect("INVALID_KEY");
  
    echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
    exit;
}
?> 

--EXPECT--

Test Successful
Test Successful
Test Successful
Test Successful
Test Successful
array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-63)
  [2]=>
  string(88) "An invalid value was specified for the keyword 'TransactionIsolation' in the DSN string."
}