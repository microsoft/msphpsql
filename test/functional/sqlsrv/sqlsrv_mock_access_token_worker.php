<?php
/**
 * Subprocess worker for sqlsrv_mock_access_token.phpt
 *
 * This script is invoked with ODBCSYSINI already set in the environment so that
 * the ODBC driver loads with connection pooling enabled. It connects to the
 * mock TDS server and verifies access token identity handling.
 *
 * Usage: php sqlsrv_mock_access_token_worker.php <server> <tokenA> <tokenB>
 */

if ($argc < 4) {
    fwrite(STDERR, "Usage: php $argv[0] <server> <tokenA> <tokenB>\n");
    exit(1);
}

$server = $argv[1];
$tokenA = $argv[2];
$tokenB = $argv[3];

function connectWithToken($server, $token, $extraOpts = []) {
    $opts = array_merge([
        "AccessToken" => $token,
        "TrustServerCertificate" => true,
        "ConnectionPooling" => true,
    ], $extraOpts);
    $conn = sqlsrv_connect($server, $opts);
    if ($conn === false) {
        echo "Connection failed:\n";
        print_r(sqlsrv_errors());
        return null;
    }
    return $conn;
}

function queryScalar($conn, $sql) {
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        echo "Query failed:\n";
        print_r(sqlsrv_errors());
        return null;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    $val = $row[0];
    sqlsrv_free_stmt($stmt);
    return $val;
}

function getUserNameWithToken($server, $token) {
    $conn = connectWithToken($server, $token);
    if ($conn === null) return null;
    $username = queryScalar($conn, "SELECT USER_NAME()");
    sqlsrv_close($conn);
    return $username;
}

// Test 1: Same token => same username
$name1a = getUserNameWithToken($server, $tokenA);
$name1b = getUserNameWithToken($server, $tokenA);

if ($name1a === null || $name1b === null) {
    echo "FAIL: Could not connect with tokenA\n";
} elseif ($name1a === $name1b) {
    echo "PASS: Same token produces same username: $name1a\n";
} else {
    echo "FAIL: Same token produced different usernames: $name1a vs $name1b\n";
}

// Test 2: Different tokens => different usernames
// Run multiple iterations to increase chance of triggering the pool key
// collision bug if the token is freed too early.
$poolBugDetected = false;
for ($i = 0; $i < 5; $i++) {
    $name2a = getUserNameWithToken($server, $tokenA);
    $name2b = getUserNameWithToken($server, $tokenB);

    if ($name2a === null || $name2b === null) {
        echo "FAIL: Could not connect with token (iteration $i)\n";
        break;
    }
    if ($name2a === $name2b) {
        $poolBugDetected = true;
        break;
    }
}

if ($poolBugDetected) {
    echo "FAIL: Different tokens produced same username (pool key collision): $name2a\n";
} elseif ($name2a !== null && $name2b !== null) {
    echo "PASS: Different tokens produce different usernames: $name2a vs $name2b\n";
}

// Test 3: Same token reuses pooled connection (same SPID)
$conn3a = connectWithToken($server, $tokenA);
$spid3a = ($conn3a !== null) ? queryScalar($conn3a, "SELECT @@SPID") : null;
sqlsrv_close($conn3a); // returns to pool

$conn3b = connectWithToken($server, $tokenA);
$spid3b = ($conn3b !== null) ? queryScalar($conn3b, "SELECT @@SPID") : null;
sqlsrv_close($conn3b);

if ($spid3a === null || $spid3b === null) {
    echo "FAIL: Could not retrieve SPID for same-token test\n";
} elseif ($spid3a === $spid3b) {
    echo "PASS: Same token reuses pooled connection (SPID $spid3a)\n";
} else {
    echo "FAIL: Same token got different SPIDs: $spid3a vs $spid3b\n";
}

// Test 4: Different tokens get different connections (different SPIDs)
$conn4a = connectWithToken($server, $tokenA);
$spid4a = ($conn4a !== null) ? queryScalar($conn4a, "SELECT @@SPID") : null;
sqlsrv_close($conn4a);

$conn4b = connectWithToken($server, $tokenB);
$spid4b = ($conn4b !== null) ? queryScalar($conn4b, "SELECT @@SPID") : null;
sqlsrv_close($conn4b);

if ($spid4a === null || $spid4b === null) {
    echo "FAIL: Could not retrieve SPID for different-token test\n";
} elseif ($spid4a !== $spid4b) {
    echo "PASS: Different tokens use different connections (SPIDs $spid4a vs $spid4b)\n";
} else {
    echo "FAIL: Different tokens reused same connection (SPID $spid4a)\n";
}

// Test 5: Custom APP + different tokens still get different connections
$conn5a = connectWithToken($server, $tokenA, ["APP" => "MyCustomApp"]);
$spid5a = ($conn5a !== null) ? queryScalar($conn5a, "SELECT @@SPID") : null;
sqlsrv_close($conn5a);

$conn5b = connectWithToken($server, $tokenB, ["APP" => "MyCustomApp"]);
$spid5b = ($conn5b !== null) ? queryScalar($conn5b, "SELECT @@SPID") : null;
sqlsrv_close($conn5b);

if ($spid5a === null || $spid5b === null) {
    echo "FAIL: Could not retrieve SPID for custom-APP test\n";
} elseif ($spid5a !== $spid5b) {
    echo "PASS: Custom APP with different tokens use different connections (SPIDs $spid5a vs $spid5b)\n";
} else {
    echo "FAIL: Custom APP with different tokens reused same connection (SPID $spid5a)\n";
}

// Test 6: Custom APP value is preserved (not overwritten by token hash)
$conn6 = connectWithToken($server, $tokenA, ["APP" => "MyCustomApp"]);
if ($conn6 !== null) {
    $appName = queryScalar($conn6, "SELECT APP_NAME()");
    sqlsrv_close($conn6);
    if ($appName === "MyCustomApp") {
        echo "PASS: Custom APP value preserved: $appName\n";
    } else {
        echo "FAIL: Custom APP value was overwritten: expected MyCustomApp, got $appName\n";
    }
} else {
    echo "FAIL: Could not connect for APP_NAME test\n";
}

echo "Done.\n";
