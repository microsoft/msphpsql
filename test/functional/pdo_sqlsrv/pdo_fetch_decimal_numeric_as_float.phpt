--TEST--
Test attribute PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE for decimal and numeric columns 
--DESCRIPTION--
Test attribute PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE for decimal and numeric columns. 
The input values are random and they are retrieved either as strings or floats. 
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
    createTable($conn, $tableName, array("c1" => "decimal(5,2)", "c2" => "numeric(5,2)"));
    
    // Insert data using bind values
    $sql = "INSERT INTO $tableName VALUES (123.45, 123.45)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Get data
    $sql = "SELECT * FROM $tableName";
    $stmt = $conn->query($sql);
    $row = $stmt->fetchAll(PDO::FETCH_NUM);

    // Print out
    var_dump($row[0][0]);
    var_dump($row[0][1]);
    
    // clean up
    dropTable( $conn, $tableName );
    unset( $stmt );
}
?>

--EXPECT--
string(6) "123.45"
string(6) "123.45"
string(6) "123.45"
string(6) "123.45"
float(123.45)
float(123.45)
Done
