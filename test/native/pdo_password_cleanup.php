<?php
// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT License.
// Run through test_pdo_password_cleanup.sh, against its instrumented module.
$module = getenv('MSPHPSQL_CLEANUP_MODULE');
if (!$module || !extension_loaded('pdo_sqlsrv') || !class_exists('FFI')) {
    throw new RuntimeException('An instrumented PDO_SQLSRV module and FFI are required');
}
$probe = FFI::cdef(
    'void cleanup_probe_reset(void);'
    . 'void cleanup_probe_expect(const char*, size_t);'
    . 'unsigned int cleanup_probe_failures(void);'
    . 'size_t cleanup_probe_released(void);',
    $module
);

function checkCleanup($label, $dsn, $username, $password, $values, $expectedCode)
{
    global $probe;
    $originalDsnHash = hash('sha256', $dsn);
    $originalPasswordHash = $password === null ? null : hash('sha256', $password);
    $probe->cleanup_probe_reset();
    foreach ($values as $value) {
        $probe->cleanup_probe_expect($value, strlen($value));
    }
    $code = null;
    try {
        $conn = new PDO($dsn, $username, $password);
        $result = $conn->query('SELECT 1');
        if ((int) $result->fetchColumn() !== 1) {
            throw new RuntimeException('Unexpected connection result');
        }
        unset($result, $conn);
    } catch (PDOException $e) {
        $code = $e->errorInfo[1] ?? $e->getCode();
    }
    $unchanged = hash('sha256', $dsn) === $originalDsnHash
        && ($password === null ? null : hash('sha256', $password)) === $originalPasswordHash;
    $released = $probe->cleanup_probe_released();
    $passed = $code === $expectedCode && $unchanged
        && $probe->cleanup_probe_failures() === 0 && $released === count($values);
    // Never print a DSN, credentials, or exception arguments on failure.
    if (!$passed) {
        echo "$label: FAIL (observed $released of ", count($values), " secure releases)\n";
        return false;
    }
    echo "$label: PASS\n";
    return true;
}

$first = 'cleanup-first-value-17';
$second = 'cleanup-second-value-18';
$prefix = 'sqlsrv:Server=127.0.0.1;Driver=CleanupInvalidDriver;';
$cases = array(
    array('factory failure', $prefix . "PWD=$first", 'cleanup-user', null, array($first), -79),
    array('parser error', $prefix . "Password=$first;UnknownKeyword=1", null, null, array($first), -42),
    array('missing server', "sqlsrv:Password=$first", null, null, array($first), -64),
    array('replacement', $prefix . "PWD=$first;Password=$second", 'cleanup-user', null, array($first, $second), -79),
    array('reverse replacement', $prefix . "Password=$first;pWd=$second", 'cleanup-user', null, array($first, $second), -79),
    array('replacement then error', $prefix . "PWD=$first;Password=$second;UnknownKeyword=1", null, null, array($first, $second), -42),
    array('unused DSN password', $prefix . "Password=$first", 'cleanup-user', 'constructor-only', array($first), -79),
    array('empty constructor wins', $prefix . "PWD=$first", 'cleanup-user', '', array($first), -79),
    array('empty password', $prefix . 'Password=', 'cleanup-user', null, array(''), -79),
    array('one-byte password', $prefix . 'Password=x', 'cleanup-user', null, array('x'), -79),
    array('empty replacement', $prefix . "PWD=$first;Password=", 'cleanup-user', null, array($first, ''), -79),
    array('replace empty password', $prefix . "PWD=;Password=$second", 'cleanup-user', null, array('', $second), -79),
    array('identical replacements', $prefix . "PWD=$first;Password=$first", 'cleanup-user', null, array($first, $first), -79),
    array('absent password', $prefix, 'cleanup-user', null, array(), -79),
    array('incomplete first password', $prefix . 'Password={' . $first, null, null, array(), -67),
    array('incomplete replacement', $prefix . "PWD=$first;Password={" . $second, null, null, array($first), -67),
    array('credential validation error', $prefix . "Password=$first}", 'cleanup-user', null, array($first . '}'), -21),
    array('access token conflict', $prefix . "Password=$first;AccessToken=dummy", null, null, array($first), -90),
    array('quoted delimiters', $prefix . 'Password={cleanup;=}}value}', 'cleanup-user', null, array('{cleanup;=}}value}'), -79)
);
$passed = true;
foreach ($cases as $case) {
    $passed = checkCleanup(...$case) && $passed;
}

if (getenv('MSPHPSQL_CLEANUP_NO_SERVER') === '1') {
    echo "Live factory-success tests not requested (no-server mode)\n";
} else {
    $server = getenv('MSSQL_SERVER');
    $username = getenv('MSSQL_USER');
    $password = getenv('MSSQL_PASSWORD');
    $driver = getenv('MSSQL_DRIVER_NAME');
    if (!$server || !$username || $password === false || !$driver) {
        throw new RuntimeException('Set MSSQL_SERVER, MSSQL_USER, MSSQL_PASSWORD, and MSSQL_DRIVER_NAME for live cleanup tests');
    }
    $dsn = 'sqlsrv:Server={' . str_replace('}', '}}', $server) . '};Driver={' . $driver . '};Encrypt=no;';
    $quoted = '{' . str_replace('}', '}}', $password) . '}';
    $passed = checkCleanup('factory success', $dsn . "Password=$quoted", $username, null, array($quoted), null) && $passed;
    $passed = checkCleanup('success with constructor override', $dsn . "PWD=$first", $username, $password, array($first), null) && $passed;
    $passed = checkCleanup('ODBC authentication failure', $dsn . "Password=$first", 'cleanup-nonexistent-user', null, array($first), 18456) && $passed;
}
$probe->cleanup_probe_reset();
exit($passed ? 0 : 1);