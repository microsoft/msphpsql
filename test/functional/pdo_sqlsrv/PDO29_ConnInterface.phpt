--TEST--
PDO Common Interface Check
--SKIPIF--
<?php require __DIR__ . '/../skipif_pdo.inc'; ?>
--FILE--
<?php
try {
    require('MsSetup.inc');

    // Expected methods in the PDO class for pdo_sqlsrv
    $expected = array(
        '__construct',
        'beginTransaction',
        'commit',
        'errorCode',
        'errorInfo',
        'exec',
        'getAttribute',
        'getAvailableDrivers',
        'inTransaction',
        'lastInsertId',
        'prepare',
        'query',
        'quote',
        'rollBack',
        'setAttribute',
    );
$phpver = substr(phpversion(), 0, 3);

if ($phpver >= '7.4') {
    // Reference: https://wiki.php.net/rfc/custom_object_serialization
    unset($expected['__wakeup']);
    unset($expected['__sleep']);
}

if ($phpver >= '8.4') {
    // PHP 8.4+: PDO class exposes connect()
    // Reference: https://wiki.php.net/rfc/pdo_driver_specific_subclasses
    $expected['connect'] = true;
}

$classname = get_class($conn);
$methods = get_class_methods($classname);
foreach ($methods as $k => $method)
{
    if (isset($expected[$method]))
    {
        unset($expected[$method]);
        unset($methods[$k]);
    }
    if ($method == $classname)
    {
        unset($expected['__construct']);
        unset($methods[$k]);
    }
}

if (!empty($expected))
{
    printf("Dumping missing class methods\n");
    var_dump($expected);
}

if (!empty($methods))
{
    printf("Found more methods than expected, dumping list\n");
    var_dump($methods);
}

    }

    // Connect
    $conn = new PDO("sqlsrv:server=$server; Database=$databaseName", $uid, $pwd);

    // Get actual methods from the object
    $actual = get_class_methods($conn);

    // Compare
    $diff = array_diff($actual, $expected);
    if (!empty($diff)) {
        echo "Found more methods than expected, dumping list:\n";
        var_dump($diff);
    }

    $diff = array_diff($expected, $actual);
    if (!empty($diff)) {
        echo "Found fewer methods than expected, dumping list:\n";
        var_dump($diff);
    }

    // Free the connection
    $conn = null;

} catch (PDOException $e) {
    echo "PDOException: " . $e->getMessage() . "\n";
}
?>
--EXPECT--