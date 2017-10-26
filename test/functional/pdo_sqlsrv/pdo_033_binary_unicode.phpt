--TEST--
Insert binary HEX data then fetch it back as string
--DESCRIPTION--
Insert binary HEX data into an nvarchar field then read it back as UTF-8 string
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    $conn = connect();

    // Create table
    $tableName = 'pdo_033test';
    createTable($conn, $tableName, array("c1" => "nvarchar(100)"));

    $input = pack("H*", '49006427500048005000');  // I'LOVE_SYMBOL'PHP
    $result;
    $stmt = insertRow($conn, $tableName, array("c1" => new BindParamOp(1, $input, "PDO::PARAM_STR", 0, "PDO::SQLSRV_ENCODING_BINARY")), "prepareBindParam", $result);

    if (!$result) {
        echo "Failed to insert!\n";
        dropTable($conn, $tableName);
        unset($stmt);
        unset($conn);
        exit;
    }

    $stmt = $conn->query("SELECT * FROM $tableName");
    $utf8 = $stmt->fetchColumn();

    echo "$utf8\n";

    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

print "Done";
?>

--EXPECT--
I❤PHP
Done
