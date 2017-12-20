--TEST--
Test error from preparing a parameterized query with direct query or emulate prepare when Column Encryption is enabled
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $connection = connect();
    $tbname = "TEST";
    createTable($connection, $tbname, array(new ColumnMeta("int", "id", "IDENTITY(1,1) NOT NULL"), "name" => "nvarchar(max)"));
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

try {
    $name = "Edward";
    $st = $connection->prepare("INSERT INTO $tbname (name) VALUES (:p0)", array(PDO::SQLSRV_ATTR_DIRECT_QUERY => true));
    $st->execute(array("p0" => $name));
} catch (PDOException $e) {
    $error = $e->errorInfo;
    // expects an exception if Column Encryption is enabled
    if (isAEConnected()) {
        if ($error[0] != "IMSSP" ||
            $error[1] != -81 ||
            $error[2] != "Parameterized statement with attribute PDO::SQLSRV_ATTR_DIRECT_QUERY is not supported in a Column Encryption enabled Connection.") {
            echo "An unexpected exception was caught.\n";
            var_dump($error);
        }
    } else {
        var_dump($error);
    }
}

try {
    $name = "Alphonse";
    $st = $connection->prepare("INSERT INTO $tbname (name) VALUES (:p0)", array(PDO::ATTR_EMULATE_PREPARES => true));
    $st->execute(array("p0" => $name));
} catch (PDOException $e) {
    $error = $e->errorInfo;
    // expects an exception if Column Encryption is enabled
    if (isAEConnected()) {
        if ($error[0] != "IMSSP" ||
            $error[1] != -82 ||
            $error[2] != "Parameterized statement with attribute PDO::ATTR_EMULATE_PREPARES is not supported in a Column Encryption enabled Connection.") {
            echo "An unexpected exception was caught.\n";
            var_dump($error);
        }
    } else {
        var_dump($error);
    }
}

try {
    dropTable($connection, $tbname);
    unset($st);
    unset($connection);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

echo "Done\n";
?>
--EXPECT--
Done
