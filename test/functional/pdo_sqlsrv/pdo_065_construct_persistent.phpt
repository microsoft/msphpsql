--TEST--
Exception is thrown if the unsupported attribute ATTR_PERSISTENT is put into the connection options
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");
// TODO: With and without column encryption in the connection string result in different behaviors
// without Column Encryption, no error is raised if PREFETCH is set before ERRMODE
// with Column Encyrption, error is raised even when PREFETCH is set before ERRMODE
// require investigation for the difference in behaviors
try {
    echo "Testing a connection with ATTR_PERSISTENT...\n";
    // setting PDO::ATTR_PERSISTENT in PDO constructor returns an exception
    $dsn = getDSN($server, $databaseName, $driver);
    $attr = array(PDO::ATTR_PERSISTENT => true);
    $conn = new PDO($dsn, $uid, $pwd, $attr);
    //free the connection
    unset($conn);
} catch (PDOException $e) {
    echo "Exception from unsupported attribute (ATTR_PERSISTENT) is caught\n";
}
try {
    echo "\nTesting new connection after exception thrown in previous connection...\n";
    $tableName1 = getTableName('tab1');
    $conn = connect();
    createTable($conn, $tableName1, array("c1" => "int", "c2" => "varchar(10)"));
    insertRow($conn, $tableName1, array("c1" => 1, "c2" => "column2"), "exec");

    $result = selectRow($conn, $tableName1, "PDO::FETCH_ASSOC");
    if ($result['c1'] == 1 && $result['c2'] == 'column2') {
        echo "Test successfully completed\n";
    }
    //free the statement and connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Testing a connection with ATTR_PERSISTENT...
Exception from unsupported attribute (ATTR_PERSISTENT) is caught

Testing new connection after exception thrown in previous connection...
Test successfully completed
