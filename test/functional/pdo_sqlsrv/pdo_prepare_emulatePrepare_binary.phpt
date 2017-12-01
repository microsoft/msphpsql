--TEST--
prepare with emulate prepare and binding binary parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
try {
    $connection_options = array();
    $connection_options[PDO::ATTR_STRINGIFY_FETCHES] = true;
    $cnn = connect("", $connection_options, PDO::ERRMODE_SILENT);

    $pdo_options = array();
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

    // Create Table
    $tbname = getTableName();
    createTable($cnn, $tbname, array("COLA" => "varbinary(max)"));

    $p = fopen('php://memory', 'a');
    fwrite($p, 'asdgasdgasdgsadg');
    rewind($p);

    //WORKS OK without emulate prepare
    print_r("Prepare without emulate prepare:\n");
    $st = $cnn->prepare("INSERT INTO $tbname VALUES(:p0)", $pdo_options);
    $st->bindParam(':p0', $p, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $st->execute();

    $st = $cnn->prepare("SELECT TOP 1 * FROM $tbname", $pdo_options);
    $st->execute();
    $value = $st->fetch(PDO::FETCH_ASSOC);
    print_r($value);
    $cnn->exec("TRUNCATE TABLE $tbname");

    //EMULATE PREPARE with SQLSRV_ENCODING_BINARY
    if (!isAEConnected()) {
        // Emulate prepare does not work fro encrypted columns
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = true;
    }
    print_r("Prepare with emulate prepare and set encoding to binary:\n");
    rewind($p);
    $st = $cnn->prepare("INSERT INTO $tbname VALUES(:p0)", $pdo_options);
    $st->bindParam(':p0', $p, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $st->execute();

    $st = $cnn->prepare("SELECT * FROM $tbname", $pdo_options);
    $st->execute();
    $value = $st->fetch(PDO::FETCH_ASSOC);
    print_r($value);
    $cnn->exec("TRUNCATE TABLE $tbname");

    //EMULATE PREPARE with no bind param options; expects an error
    print_r("Prepare with emulate prepare and no bindparam options:\n");
    rewind($p);

    $st = $cnn->prepare("INSERT INTO $tbname VALUES(:p0)", $pdo_options);
    $st->bindParam(':p0', $p, PDO::PARAM_LOB);
    $st->execute();
    $error = $st->errorInfo();
    if (!isAEConnected() && $error[0] !== "42000") {
        echo "Error 42000 is expected: Implicit conversion from data type varchar to varbinary(max) is not allowed.\n";
        var_dump($error);
    } elseif (isAEConnected() && $error[0] != "22018") {
        echo "Error 22018 is expected: Invalid character value for cast specification.\n";
        var_dump($error);
    } else {
        echo "Done.\n";
    }

    dropTable($cnn, $tbname);
    unset($st);
    unset($cnn);
} catch (PDOException $e) {
    print_r($e->errorInfo[2] . "\n");
}
?>
--EXPECTREGEX--
Prepare without emulate prepare:
Array
\(
    \[COLA\] => asdgasdgasdgsadg
\)
Prepare with emulate prepare and set encoding to binary:
Array
\(
    \[COLA\] => asdgasdgasdgsadg
\)
Prepare with emulate prepare and no bindparam options:
Done\.
