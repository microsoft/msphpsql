--TEST--
Test emulate prepare utf8 encoding set at the statement level
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $pdo_options = [];
    $pdo_options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;

    $connection = connect('', $pdo_options);

    // Always Encrypted does not support using DIRECT_QUERY for binding parameters
    // see https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam
    $pdo_options = [];
    if (!isAEConnected()) {
        $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = true;
    }
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;
    $pdo_options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;

    // Create table
    $tbname = "TEST";
    createTable($connection, $tbname, array( new ColumnMeta("int", "id", "IDENTITY(1,1) NOT NULL"), "name" => "nvarchar(max)"));

    $prefix = '가각';
    $name = '가각ácasa';
    $name2 = '가각sample2';

    $pdo_options[PDO::ATTR_EMULATE_PREPARES] = false;
    $st = $connection->prepare("INSERT INTO $tbname (name) VALUES (:p0)", $pdo_options);
    $st->execute(['p0' => $name]);

    // Always Encrypted does not support emulate prepare
    if (!isAEConnected()) {
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = true;
    }
    $st = $connection->prepare("INSERT INTO $tbname (name) VALUES (:p0)", $pdo_options);
    $st->execute(['p0' => $name2]);

    if (!isAEConnected()) {
        $statement1 = $connection->prepare("SELECT * FROM $tbname WHERE NAME LIKE :p0", $pdo_options);
        $statement1->execute(['p0' => "$prefix%"]);
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = false;
        $statement2 = $connection->prepare("SELECT * FROM $tbname WHERE NAME LIKE :p0", $pdo_options);
        $statement2->execute(['p0' => "$prefix%"]);
    } else {
        $statement1 = $connection->prepare("SELECT * FROM $tbname", $pdo_options);
        $statement1->execute();
        $statement2 = $connection->prepare("SELECT * FROM $tbname", $pdo_options);
        $statement2->execute();
    }
    foreach ($statement1 as $row) {
        echo 'FOUND: ' . $row['name'] . "\n";
    }
    foreach ($statement2 as $row) {
        echo 'FOUND: ' . $row['name'] . "\n";
    }

    dropTable($connection, $tbname);
    unset($stmt);
    unset($connection);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
FOUND: 가각ácasa
FOUND: 가각sample2
FOUND: 가각ácasa
FOUND: 가각sample2
