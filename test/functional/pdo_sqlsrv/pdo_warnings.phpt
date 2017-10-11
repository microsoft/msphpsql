--TEST--
Test warnings on connection and statement levels
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
try {
    require_once("MsCommon_mid-refactor.inc");

    $conn = connect("", array(), PDO::ERRMODE_WARNING);
    // raise a warning in connection
    $conn->getAttribute(PDO::ATTR_TIMEOUT);

    $tbname = "table1";
    dropTable($conn, $tbname);

    // raise a warning in statement
    $statement = $conn->prepare("CRATE TABLE table1(id INT NOT NULL PRIMARY KEY, val VARCHAR(10))");
    $statement->execute();

    unset($statement);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
    exit;
}
?>
--EXPECTREGEX--
Warning: SQLSTATE: IMSSP
Error Code: -38
Error Message: An unsupported attribute was designated on the PDO object\.
 in .+(\/|\\)pdo_warnings\.php on line [0-9]+

Warning: PDO::getAttribute\(\): SQLSTATE\[IM001\]: Driver does not support this function: driver does not support that attribute in .+(\/|\\)pdo_warnings\.php on line [0-9]+

Warning: PDOStatement::execute\(\): SQLSTATE\[42000\]: Syntax error or access violation: 156 \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Incorrect syntax near the keyword 'TABLE'\. in .+(\/|\\)pdo_warnings\.php on line [0-9]+
