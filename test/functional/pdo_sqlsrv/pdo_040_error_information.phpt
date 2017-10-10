--TEST--
Retrieve error information; supplied values does not match table definition
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    // set errmode to silent to compare sqlstates in the test
    $conn = connect("", array(), "silent");

    // Create table
    $tableName = 'pdo_040test';
    // common function insertRow() is not used here since the test deliberately executes an invalid insertion statement
    // thus it's not necessary to create an encrypted column for testing column encryption
    $sql = "CREATE TABLE $tableName (code INT)";
    $stmt = $conn->exec($sql);

    // Insert data using bind parameters
    // Number of supplied values does not match table definition
    $sql = "INSERT INTO $tableName VALUES (?,?)";
    $stmt = $conn->prepare($sql);
    $params = array(2010,"London");

    // SQL statement has an error, which is then reported
    $stmt->execute($params);
    $error = $stmt->errorInfo();

    $success = true;
    if (!isColEncrypted()) {
        // 21S01 is the expected ODBC Column name or number of supplied values does not match table definition error
        if ($error[0] != "21S01") {
            $success = false;
        }
    } else {
        // 07009 is the expected ODBC Invalid Descriptor Index error
        if ($error[0] != "07009") {
            $success = false;
        }
    }

    // Close connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);

    if ($success) {
        print "Done";
    } else {
        var_dump($error);
    }
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
Done
