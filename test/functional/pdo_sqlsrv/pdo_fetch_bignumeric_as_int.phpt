--TEST--
Test attribute PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE for bigint columns 
--DESCRIPTION--
Test attribute PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE for bigint columns. 
The input value is a bigint bigger than the max int value in mssql. 
Note that the existing attribute ATTR_STRINGIFY_FETCHES should have no effect on data retrieval.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $tableName = "pdo_test_table";

    // Connect
    $conn = connect();

    // Run test
    Test();

    // Set PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE = false (default)
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, FALSE);
    Test();

    // Set PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE = true
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, TRUE);
    Test();

    // Close connection
    unset($stmt);
    unset($conn);

    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

// Generic test starts here
function Test()
{
    global $conn, $tableName;

    // Drop table if exists
    createTable($conn, $tableName, array("c1" => "bigint"));
    
    // Insert data using bind values
    $sql = "INSERT INTO $tableName VALUES (32147483647)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Get data
    $sql = "SELECT * FROM $tableName";
    $stmt = $conn->query($sql);
    $row = $stmt->fetchAll(PDO::FETCH_NUM);

    // Print out
    for ($i=0; $i<$stmt->rowCount(); $i++) {
        var_dump($row[$i][0]);
    }
    
    // clean up
    dropTable( $conn, $tableName );
    unset( $stmt );
}
?>

--EXPECT--
string(11) "32147483647"
string(11) "32147483647"
int(32147483647)
Done
