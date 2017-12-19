--TEST--
Test sql_variant as an output parameter
--DESCRIPTION--
Since output param is not supported for sql_variant columns, this test verifies a proper error message is returned
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
﻿<?php
require_once("MsCommon_mid-refactor.inc");

function testSimpleSelect($conn, $tableName)
{
    $count = 0;

    if (!isAEConnected()) {
        $stmt = $conn->prepare("SELECT ? = COUNT(* ) FROM $tableName");
        $stmt->bindParam(1, $count, PDO::PARAM_INT, 4);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $tableName");
        $stmt->execute();
        $count = $stmt->fetch()[0];
    }
    echo "Number of rows: $count\n";

    $value = 'xx';

    $stmt = $conn->prepare("SELECT ? = c2_variant FROM $tableName");
    $stmt->bindParam(1, $value, PDO::PARAM_STR, 50);
    $stmt->execute();
    echo "Variant column: $value\n\n";
}

function createVariantTable($conn, $tableName)
{
    try {
        createTable($conn, $tableName, array("c1_int" => "int", "c2_variant" => "sql_variant"));
    } catch (Exception $e) {
        echo "Failed to create a test table\n";
        echo $e->getMessage();
    }

    $data = "This is to test if sql_variant works with output parameters";
    if (!isColEncrypted()) {
        $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (1, ?)";
        $stmt = $conn->prepare($tsql);
        $result = $stmt->execute(array($data));
    } else {
        $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (?, ?)";
        $stmt = $conn->prepare($tsql);
        $intData = 1;
        $result = $stmt->execute(array($intData, $data));
    }

    if (! $result) {
        echo "Failed to insert data\n";
    }
}

try {
    // Connect
    $conn = connect();

    // Now test with another stored procedure
    $tableName = getTableName();
    createVariantTable($conn, $tableName);

    // Test a simple select to get output
    testSimpleSelect($conn, $tableName);

    dropTable($conn, $tableName);
    unset($conn);
} catch (Exception $e) {
    $error = $e->getMessage();
    if (!(!isAEConnected() && strpos($error, "Implicit conversion from data type sql_variant to nvarchar(max) is not allowed. Use the CONVERT function to run this query.") !== false) &&
        !(isAEConnected() && strpos($error, "Invalid Descriptor Index") !== false)) {
        echo $error;
    }
}
echo "Done\n";

?>
--EXPECT--
﻿Number of rows: 1
Done
