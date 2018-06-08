--TEST--
Retrieve error information; supplied values does not match table definition
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    $conn = connect();

    // Create table
    $tableName = 'pdo_040test';
    dropTable($conn, $tableName);

    // common function insertRow() is not used here since the test deliberately
    // executes an invalid insertion statement
    // thus it's not necessary to create an encrypted column for testing column encryption
    $sql = "CREATE TABLE $tableName (code INT)";
    $stmt = $conn->exec($sql);

    // Insert data using bind parameters
    // Number of supplied values does not match table definition
    $sql = "INSERT INTO $tableName VALUES (?,?)";
    $stmt = $conn->prepare($sql);
    $params = array(2010, "London");

    // SQL statement has an error, which is then reported
    if ($stmt) {
        $stmt->execute($params);
    }
} catch (PDOException $e) {
    $error = $e->errorInfo;
    $success = false;
    if (!isAEConnected()) {
        // 21S01 is the expected ODBC Column name or number of supplied values does not match table definition error
        if ($error[0] === "21S01") {
            $success = true;
        }
    } else {
        // 07009 is the expected ODBC Invalid Descriptor Index error
        if ($error[0] === "07009") {
            $success = true;
        }
    }
    if ($success) {
        print "Done";
    } else {
        var_dump($error);
    }
} finally {
    // Clean up and close connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
}
?>

--EXPECT--
Done
