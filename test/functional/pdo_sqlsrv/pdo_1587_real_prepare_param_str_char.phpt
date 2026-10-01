--TEST--
GitHub issue 1587 - Real prepared statements declare PDO::PARAM_STR_CHAR strings as varchar
--DESCRIPTION--
A UTF-8 string parameter is declared as nvarchar unless the application marks it as a non-national
string, per binding with PDO::PARAM_STR_CHAR or for the connection with PDO::ATTR_DEFAULT_STR_PARAM.
Comparing an nvarchar parameter with a varchar column makes SQL Server convert the column, which
prevents index seeks on it and hides its statistics from the optimizer. This test checks the type
each parameter is declared with, and that non-national values longer than 4000 bytes are sent in full.
--SKIPIF--
<?php require('skipif_old_php.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

// SQL_VARIANT_PROPERTY reports the type the driver declared for the parameter.
function declaredType($conn, $value, $pdoType, $bindParam = false, $encoding = null)
{
    $sql = "SELECT CAST(SQL_VARIANT_PROPERTY(CAST(? AS sql_variant), 'BaseType') AS varchar(30))";
    $stmt = $conn->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => false));
    if ($bindParam && $encoding !== null) {
        $stmt->bindParam(1, $value, $pdoType, 0, $encoding);
    } elseif ($bindParam) {
        $stmt->bindParam(1, $value, $pdoType);
    } else {
        $stmt->bindValue(1, $value, $pdoType);
    }
    $stmt->execute();

    return $stmt->fetchColumn();
}

function check($label, $actual, $expected)
{
    echo str_pad($label, 52), $actual === $expected ? "$actual" : "FAIL: expected $expected, got " . var_export($actual, true), PHP_EOL;
}

try {
    // Column encryption is disabled so that the declared types are the driver's own.
    $conn = connect('', array(), PDO::ERRMODE_EXCEPTION, true);

    echo "Default connection:\n";
    check('PARAM_STR', declaredType($conn, 'VA', PDO::PARAM_STR), 'nvarchar');
    check('PARAM_STR | PARAM_STR_CHAR', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR), 'varchar');
    check('PARAM_STR | PARAM_STR_NATL', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_NATL), 'nvarchar');
    check('bindParam PARAM_STR | PARAM_STR_CHAR', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR, true), 'varchar');
    // An explicit parameter encoding other than UTF-8 keeps its own declared type.
    check('PARAM_STR_CHAR with SQLSRV_ENCODING_SYSTEM', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR, true, PDO::SQLSRV_ENCODING_SYSTEM), 'varchar');
    check('PARAM_STR_NATL with SQLSRV_ENCODING_SYSTEM', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_NATL, true, PDO::SQLSRV_ENCODING_SYSTEM), 'varchar');
    check('PARAM_STR_CHAR with SQLSRV_ENCODING_BINARY', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR, true, PDO::SQLSRV_ENCODING_BINARY), 'varbinary');
    check('PARAM_STR_CHAR with SQLSRV_ENCODING_UTF8', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR, true, PDO::SQLSRV_ENCODING_UTF8), 'varchar');
    // Non-string values are not affected.
    check('PARAM_INT', declaredType($conn, 7, PDO::PARAM_INT), 'int');

    echo "ATTR_DEFAULT_STR_PARAM = PARAM_STR_CHAR:\n";
    $conn->setAttribute(PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_CHAR);
    check('PARAM_STR', declaredType($conn, 'VA', PDO::PARAM_STR), 'varchar');
    check('PARAM_STR | PARAM_STR_NATL', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_NATL), 'nvarchar');

    echo "ATTR_DEFAULT_STR_PARAM = PARAM_STR_NATL:\n";
    $conn->setAttribute(PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_NATL);
    check('PARAM_STR', declaredType($conn, 'VA', PDO::PARAM_STR), 'nvarchar');
    check('PARAM_STR | PARAM_STR_CHAR', declaredType($conn, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR), 'varchar');

    // A non-national value longer than 4000 bytes must be declared varchar(max), not truncated.
    $tableName = 'test1587';
    dropTable($conn, $tableName);
    $conn->exec("CREATE TABLE $tableName (ID int identity(1,1), Code varchar(10), Body varchar(max))");
    $long = str_repeat('abcdefghij', 900);
    $stmt = $conn->prepare("INSERT INTO $tableName (Code, Body) VALUES (?, ?)");
    $stmt->bindValue(1, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR);
    $stmt->bindValue(2, $long, PDO::PARAM_STR | PDO::PARAM_STR_CHAR);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT DATALENGTH(Body) FROM $tableName WHERE Code = ?");
    $stmt->bindValue(1, 'VA', PDO::PARAM_STR | PDO::PARAM_STR_CHAR);
    $stmt->execute();
    echo "Long value: ", $stmt->fetchColumn() === strval(strlen($long)) ? "sent in full" : "FAIL: truncated", PHP_EOL;

    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
    echo "Done\n";
} catch (PDOException $e) {
    echo $e->getMessage(), PHP_EOL;
}
?>
--EXPECT--
Default connection:
PARAM_STR                                           nvarchar
PARAM_STR | PARAM_STR_CHAR                          varchar
PARAM_STR | PARAM_STR_NATL                          nvarchar
bindParam PARAM_STR | PARAM_STR_CHAR                varchar
PARAM_STR_CHAR with SQLSRV_ENCODING_SYSTEM          varchar
PARAM_STR_NATL with SQLSRV_ENCODING_SYSTEM          varchar
PARAM_STR_CHAR with SQLSRV_ENCODING_BINARY          varbinary
PARAM_STR_CHAR with SQLSRV_ENCODING_UTF8            varchar
PARAM_INT                                           int
ATTR_DEFAULT_STR_PARAM = PARAM_STR_CHAR:
PARAM_STR                                           varchar
PARAM_STR | PARAM_STR_NATL                          nvarchar
ATTR_DEFAULT_STR_PARAM = PARAM_STR_NATL:
PARAM_STR                                           nvarchar
PARAM_STR | PARAM_STR_CHAR                          varchar
Long value: sent in full
Done
