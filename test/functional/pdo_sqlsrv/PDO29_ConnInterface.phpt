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

    // Fix for PHP 8.4: The PDO class now includes a 'connect' method.
    // We add it to the expected list if running on PHP 8.4+ (ID 80400)
    if (PHP_VERSION_ID >= 80400) {
        $expected[] = 'connect';
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