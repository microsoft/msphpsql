--TEST--
Test PDO::__Construct connection option TransactionIsolation with invalid value
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
}

// TEST BEGIN
try {
Connect("INVALID_KEY");
  
  echo "Test Successful";
}
catch( PDOException $e ) {
    var_dump( $e->errorInfo );
}
?> 

--EXPECT--

array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-63)
  [2]=>
  string(88) "An invalid value was specified for the keyword 'TransactionIsolation' in the DSN string."
}