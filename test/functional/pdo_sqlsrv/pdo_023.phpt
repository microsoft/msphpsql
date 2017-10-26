--TEST--
Bind values with PDO::PARAM_BOOL, enable/disable fetch numeric type attribute
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once( "MsCommon_mid-refactor.inc" );

try {
    // Sample data
    $sample = array([true, false],[-12, 0x2A],[0.00, NULL]);
    $tableName = "pdo_test_table";

    // Connect
    $conn = connect();

    // Run test
    Test();

    // Set PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE = false (default)
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, FALSE);
    Test();

    // Set PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE = true
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, TRUE);
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
    global $conn, $tableName, $sample;

    // Drop table if exists
    createTable($conn, $tableName, array("c1" => "int", "c2" => "bit"));
    
    // Insert data using bind values
    $sql = "INSERT INTO $tableName VALUES (:v1, :v2)";
    $stmt = $conn->prepare($sql);
    foreach ($sample as $s) {
        $stmt->bindValue(':v1', $s[0], PDO::PARAM_BOOL);
        $stmt->bindValue(':v2', $s[1], PDO::PARAM_BOOL);
        $stmt->execute();
    }

    // Get data
    $sql = "SELECT * FROM $tableName";
    $stmt = $conn->query($sql);
    $row = $stmt->fetchAll(PDO::FETCH_NUM);

    // Print out
    for ($i=0; $i<$stmt->rowCount(); $i++) {
        var_dump($row[$i][0]); var_dump($row[$i][1]);
    }
    
    // clean up
    dropTable( $conn, $tableName );
    unset( $stmt );
}
?>

--EXPECT--
string(1) "1"
string(1) "0"
string(1) "1"
string(1) "1"
string(1) "0"
NULL
string(1) "1"
string(1) "0"
string(1) "1"
string(1) "1"
string(1) "0"
NULL
int(1)
int(0)
int(1)
int(1)
int(0)
NULL
Done
