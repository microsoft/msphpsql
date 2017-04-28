--TEST--
Exception is thrown if the unsupported attribute ATTR_PERSISTENT is put into the connection options
--SKIPIF--
--FILE--
<?php
include 'pdo_tools.inc';
require_once("autonomous_setup.php");
$database = "tempdb";
$dsn = "sqlsrv:Server = $serverName;Database = $database;";
try{
    echo "Testing a connection with ATTR_PERSISTENT...\n";
    // setting PDO::ATTR_PERSISTENT in PDO constructor returns an exception
    $attr = array(PDO::ATTR_PERSISTENT => true); 
    $conn = new PDO( $dsn, $username, $password, $attr); 
   
    //free the connection 
    $conn=null;
}
catch( PDOException $e ) {
    echo "Exception from unsupported attribute (ATTR_PERSISTENT) is caught\n";
    //exit;
}
try{
    echo "\nTesting new connection after exception thrown in previous connection...\n";
    $tableName1 = GetTempTableName('tab1', false);
    $conn = new PDO( $dsn, $username, $password ); 
    $sql = "CREATE TABLE $tableName1 (c1 int, c2 varchar(10))";
    $stmt = $conn->query($sql);
    $ret = $conn->exec("INSERT INTO $tableName1 VALUES(1, 'column2')");
    $stmt = $conn->query("SELECT * FROM $tableName1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['c1'] == 1 && $result['c2'] == 'column2') {
        echo "Test successfully";
    }
    //free the statement and connection 
    $stmt = null;
    $conn = null;
}
catch( PDOException $e ) {
    var_dump( $e);
}
?> 
--EXPECT--
Testing a connection with ATTR_PERSISTENT...
Exception from unsupported attribute (ATTR_PERSISTENT) is caught

Testing new connection after exception thrown in previous connection...
Test successfully
