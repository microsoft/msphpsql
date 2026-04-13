--TEST--
Test access token identity with connection pooling via mock TDS server (sqlsrv)
--DESCRIPTION--
Uses the Python mock TDS server to verify that:
1. Two connections with the SAME access token get the SAME USER_NAME()
2. Two connections with DIFFERENT access tokens get DIFFERENT USER_NAME()
3. Same token reuses the pooled connection (same @@SPID)
4. Different tokens get separate connections (different @@SPID)
5. Custom APP option with different tokens still get separate connections
6. Custom APP value is preserved in the Login7 app_name (not overwritten by token hash)
On Linux/macOS, pooling is enabled via a custom odbcinst.ini with CPTimeout on
each driver section and the test runs in a subprocess with ODBCSYSINI set.
On Windows, pooling is controlled by the ConnectionPooling option.
--SKIPIF--
<?php
if (!extension_loaded("sqlsrv")) {
    die("skip sqlsrv extension not loaded");
}
require_once('mock_tds_helper.inc');
$mock = start_mock_tds_server();
stop_mock_tds_server($mock);
?>
--FILE--
<?php
require_once('mock_tds_helper.inc');

$tokenA = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.mocktokenA';
$tokenB = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.mocktokenB';

$mock = start_mock_tds_server([
    '--map-token', "$tokenA=alice",
    '--map-token', "$tokenB=bob",
]);

$server = "127.0.0.1,{$mock['port']}";

// On Linux/macOS, create custom odbcinst.ini with pooling enabled.
// On Windows, this returns null (pooling is controlled via ConnectionPooling option).
$pooldir = create_pooling_odbcinst();

$subprocess = dirname(__FILE__) . '/sqlsrv_mock_access_token_worker.php';
$stderr_file = sys_get_temp_dir() . '/mock_tds_worker_' . getmypid() . '.stderr';
$cmd = build_worker_command($subprocess, $pooldir, [$server, $tokenA, $tokenB], $stderr_file);

$output = shell_exec($cmd);
if (empty(trim($output ?? ''))) {
    echo "FAIL: Worker subprocess produced no output.\n";
    // Dump worker stderr for diagnosis
    if (file_exists($stderr_file)) {
        $stderr = trim(file_get_contents($stderr_file));
        if ($stderr !== '') {
            echo "Worker stderr:\n$stderr\n";
        }
    }
    // Dump mock server log for diagnosis
    $logfile = $mock['tmpdir'] . DIRECTORY_SEPARATOR . 'server.log';
    if (file_exists($logfile)) {
        $log = trim(file_get_contents($logfile));
        if ($log !== '') {
            $lines = explode("\n", $log);
            echo "Server log (last 30 lines):\n";
            echo implode("\n", array_slice($lines, -30)) . "\n";
        }
    }
    // Dump the constructed command for debugging
    echo "Command: $cmd\n";
} else {
    echo $output;
}
@unlink($stderr_file);

cleanup_pooling_odbcinst($pooldir);
stop_mock_tds_server($mock);
?>
--CLEAN--
<?php
require_once('mock_tds_helper.inc');
cleanup_orphaned_mock_servers();
// Clean up any stale pooling dirs (glob because getmypid() differs in --CLEAN--)
foreach (glob(sys_get_temp_dir() . '/mock_tds_pool_*') as $d) {
    @unlink($d . '/odbcinst.ini');
    @rmdir($d);
}
// Clean up worker stderr files
foreach (glob(sys_get_temp_dir() . '/mock_tds_worker_*.stderr') as $f) {
    @unlink($f);
}
?>
--EXPECTF--
PASS: Same token produces same username: alice
PASS: Different tokens produce different usernames: alice vs bob
PASS: Same token reuses pooled connection (SPID %d)
PASS: Different tokens use different connections (SPIDs %d vs %d)
PASS: Custom APP with different tokens use different connections (SPIDs %d vs %d)
PASS: Custom APP value preserved: MyCustomApp
Done.
