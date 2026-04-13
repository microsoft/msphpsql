<?php
/**
 * Helper to start/stop the Python mock TDS server for PHPT tests.
 * Works on Linux, macOS, and Windows.
 *
 * Usage:
 *   require_once('mock_tds_helper.inc');
 *   $mock = start_mock_tds_server();  // returns ['proc' => resource, 'port' => int, 'pipes' => [...]]
 *   // ... run tests against 127.0.0.1,$mock['port'] ...
 *   stop_mock_tds_server($mock);
 */

function is_windows()
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

/**
 * Find a working Python 3 interpreter.
 * Returns the command name, or null if not found.
 */
function find_python()
{
    $candidates = ['python3', 'python'];
    foreach ($candidates as $python) {
        $ret = null;
        $output = [];
        exec("$python --version 2>&1", $output, $ret);
        if ($ret === 0) {
            return $python;
        }
    }
    return null;
}

/**
 * Kill a process (and its children) by PID, cross-platform.
 */
function kill_process_tree($pid)
{
    $pid = intval($pid);
    if ($pid <= 0) {
        return;
    }
    if (is_windows()) {
        // /T kills process tree, /F forces termination
        exec("taskkill /F /T /PID $pid 2>NUL");
    } elseif (function_exists('posix_kill')) {
        @posix_kill($pid, 15); // SIGTERM
        usleep(200000);
        @posix_kill($pid, 9);  // SIGKILL
    } else {
        // posix extension not available (e.g. macOS CI) — fall back to shell kill
        exec("kill -15 $pid 2>/dev/null");
        usleep(200000);
        exec("kill -9 $pid 2>/dev/null");
    }
}

/**
 * Start the mock TDS server in background.
 *
 * @param array $extra_args  Additional CLI args (e.g. ['--map-token', 'tok=user'])
 * @param int   $timeout     Max seconds to wait for PORT=N output
 * @return array  ['proc' => resource, 'port' => int, 'pipes' => [...], 'cert' => string, 'key' => string]
 */
function start_mock_tds_server($extra_args = [], $timeout = 10)
{
    $script = realpath(__DIR__ . '/../../tools/mock_tds_server.py');
    if (!$script) {
        die("skip mock_tds_server.py not found");
    }

    // Use a temp dir for the self-signed cert
    $tmpdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mock_tds_' . getmypid();
    if (!is_dir($tmpdir)) {
        mkdir($tmpdir, 0700, true);
    }
    $cert = $tmpdir . DIRECTORY_SEPARATOR . 'server.pem';
    $key  = $tmpdir . DIRECTORY_SEPARATOR . 'server.key';
    $logfile = $tmpdir . DIRECTORY_SEPARATOR . 'server.log';

    $python = find_python();
    if ($python === null) {
        die("skip python not found");
    }

    $cmd_args = [
        $python, $script,
        '--port', '0',
        '--print-port',
        '--generate-cert',
        '--cert', $cert,
        '--key', $key,
        '--log-level', 'DEBUG',
        '--log-file', $logfile,
    ];
    $cmd_args = array_merge($cmd_args, $extra_args);

    $descriptors = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ];

    $cmd = implode(' ', array_map('escapeshellarg', $cmd_args));

    // On Linux/macOS, proc_open uses "sh -c 'cmd'" which means
    // proc_get_status()['pid'] returns the shell PID, not Python's.
    // Prepend "exec" so the shell replaces itself with Python, making
    // the PID match and allowing proc_terminate/posix_kill to reach it.
    if (!is_windows()) {
        $cmd = 'exec ' . $cmd;
    }

    $proc = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($proc)) {
        die("skip failed to start mock TDS server");
    }

    // Read PORT=N from stdout
    stream_set_blocking($pipes[1], false);
    $port = null;
    $deadline = microtime(true) + $timeout;

    while (microtime(true) < $deadline) {
        $line = fgets($pipes[1]);
        if ($line !== false) {
            $line = trim($line);
            if (preg_match('/^PORT=(\d+)$/', $line, $m)) {
                $port = (int)$m[1];
                break;
            }
        }
        usleep(50000); // 50ms
    }

    if ($port === null) {
        // Read stderr for diagnostics
        stream_set_blocking($pipes[2], false);
        $err = stream_get_contents($pipes[2]);
        proc_terminate($proc);
        proc_close($proc);
        die("skip mock TDS server didn't output PORT=N within {$timeout}s. stderr: $err");
    }

    // Set stdout/stderr to non-blocking for later reads
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    // Get the server PID and write it to a well-known file so --CLEAN-- can
    // kill it even if the --FILE-- section crashes before stop_mock_tds_server().
    $status = proc_get_status($proc);
    $pid = $status['pid'];
    $pidfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mock_tds_server_' . getmypid() . '.pid';
    file_put_contents($pidfile, (string)$pid);

    $mock = [
        'proc'    => $proc,
        'port'    => $port,
        'pipes'   => $pipes,
        'cert'    => $cert,
        'key'     => $key,
        'tmpdir'  => $tmpdir,
        'pid'     => $pid,
        'pidfile' => $pidfile,
    ];

    // Safety net: if the PHP process exits without calling stop_mock_tds_server(),
    // kill the server anyway to avoid orphaned processes.
    register_shutdown_function(function () use ($mock) {
        if (isset($mock['pid']) && $mock['pid'] > 0) {
            kill_process_tree($mock['pid']);
        }
        if (isset($mock['pidfile'])) {
            @unlink($mock['pidfile']);
        }
    });

    return $mock;
}

/**
 * Create a custom odbcinst.ini with connection pooling enabled (Linux/macOS only).
 *
 * On Linux/macOS, ODBC pooling is controlled by odbcinst.ini and the driver
 * must be loaded fresh for changes to take effect (see MS docs). CPTimeout
 * must be set on each driver section, not just [ODBC].
 *
 * On Windows, pooling is controlled by the ConnectionPooling option in the
 * connection string, so this function is not needed (returns null).
 *
 * Returns the directory path to use as ODBCSYSINI (or null on Windows).
 * The caller must set this env var BEFORE spawning a new PHP process —
 * putenv() in the current process is too late because the ODBC driver
 * is already loaded.
 *
 * @param string|null $tmpdir  Directory to write the file (auto-generated if null)
 * @return string|null  Path to the temp dir containing odbcinst.ini, or null on Windows
 */
function create_pooling_odbcinst($tmpdir = null)
{
    if (is_windows()) {
        return null;
    }

    if ($tmpdir === null) {
        $tmpdir = sys_get_temp_dir() . '/mock_tds_pool_' . getmypid();
    }
    if (!is_dir($tmpdir)) {
        mkdir($tmpdir, 0700, true);
    }

    // Discover default odbcinst.ini location
    $src = null;
    $lines = @shell_exec("odbcinst -j 2>/dev/null");
    if ($lines) {
        foreach (explode("\n", $lines) as $line) {
            if (stripos($line, 'DRIVERS') !== false) {
                $parts = explode(":", $line, 2);
                $path = trim($parts[1] ?? '');
                if ($path && file_exists($path)) {
                    $src = $path;
                    break;
                }
            }
        }
    }
    if (!$src && file_exists('/etc/odbcinst.ini')) {
        $src = '/etc/odbcinst.ini';
    }

    // Parse the existing file and inject CPTimeout into each driver section
    $content = $src ? file_get_contents($src) : '';
    $sections = [];
    $current_section = null;
    $has_odbc_section = false;

    foreach (explode("\n", $content) as $line) {
        if (preg_match('/^\[(.+)\]/', $line, $m)) {
            $current_section = $m[1];
            if ($current_section === 'ODBC') {
                $has_odbc_section = true;
            }
        }
        $sections[$current_section][] = $line;
    }

    // Write the new file
    $dst = $tmpdir . '/odbcinst.ini';
    $out = '';
    foreach ($sections as $section => $lines) {
        $block = implode("\n", $lines);
        // Add CPTimeout to driver sections (those with a Driver= key)
        if ($section !== 'ODBC' && $section !== null && stripos($block, 'Driver=') !== false) {
            if (stripos($block, 'CPTimeout') === false) {
                $block = rtrim($block) . "\nCPTimeout=120\n";
            }
        }
        $out .= $block . "\n";
    }

    // Ensure [ODBC] Pooling=Yes exists
    if (!$has_odbc_section) {
        $out .= "\n[ODBC]\nPooling=Yes\n";
    } else {
        // Make sure Pooling=Yes is in the [ODBC] section
        if (stripos($out, 'Pooling=') === false) {
            $out = str_replace('[ODBC]', "[ODBC]\nPooling=Yes", $out);
        } else {
            $out = preg_replace('/Pooling\s*=\s*\S+/i', 'Pooling=Yes', $out);
        }
    }

    file_put_contents($dst, $out);
    return $tmpdir;
}

/**
 * Clean up the temporary pooling odbcinst.ini directory.
 *
 * @param string|null $pooldir  Path returned by create_pooling_odbcinst()
 */
function cleanup_pooling_odbcinst($pooldir)
{
    if ($pooldir === null) {
        return;
    }
    @unlink($pooldir . DIRECTORY_SEPARATOR . 'odbcinst.ini');
    @rmdir($pooldir);
}

/**
 * Build a shell command to run a worker PHP script with connection pooling.
 *
 * On Linux/macOS, sets ODBCSYSINI before invoking PHP so the ODBC driver
 * loads with pooling enabled. On Windows, pooling is controlled by the
 * ConnectionPooling option in the connection string, so no env var is needed.
 *
 * @param string      $worker_script  Absolute path to the worker .php file
 * @param string|null $pooldir        Path returned by create_pooling_odbcinst() (null on Windows)
 * @param array       $args           Additional CLI arguments for the worker
 * @return string     Shell command ready for shell_exec()
 */
function build_worker_command($worker_script, $pooldir, $args = [], $stderr_file = null)
{
    $parts = [];
    if (!is_windows() && $pooldir !== null) {
        $parts[] = "export ODBCSYSINI=" . escapeshellarg($pooldir);
    }
    $php_cmd = escapeshellarg(PHP_BINARY);

    // Forward PHP INI / extension configuration so the worker subprocess
    // loads the same extensions (sqlsrv / pdo_sqlsrv) as the parent.
    //
    // run-tests.php captures the test process with "2>&1", merging stderr
    // into stdout. Since shell_exec() inherits stderr, any PHP startup
    // warnings (e.g. "Module already loaded") from the subprocess would
    // leak into the captured output and cause an --EXPECT-- mismatch.
    // We append 2>/dev/null (or 2>NUL on Windows) to suppress this.
    //
    // Extensions can reach the parent process from three sources:
    //   1. Main php.ini loaded with -c (macOS CI — custom php.ini with
    //      extension=<full_path> lines; or prebuilt/PECL modes)
    //   2. Scan directory / conf.d (Linux — e.g. /etc/php.d/20-sqlsrv.ini)
    //   3. CLI -d flags from run-tests.php forwarding
    //
    // Strategy: try to detect HOW the extensions are loaded and replicate
    // that for the subprocess. When in doubt, forward -d extension= flags
    // and rely on stderr suppression to hide any double-load warnings.

    $extForwarded = false;

    // Case 1: A php.ini is loaded and it contains extension=…sqlsrv lines.
    // Forward it with -c so the subprocess gets the same directives.
    $ini = php_ini_loaded_file();
    if ($ini !== false && $ini !== '') {
        $content = @file_get_contents($ini);
        if ($content !== false && preg_match('/^\s*extension\s*=.*sqlsrv/mi', $content)) {
            $php_cmd .= ' -c ' . escapeshellarg($ini);
            $extForwarded = true;
        }
    }

    // Case 2: Extensions live in conf.d / scan directory (typical Linux
    // package installs). The subprocess uses the same PHP binary, so it
    // will discover the same scan directory automatically — no flags needed.
    if (!$extForwarded) {
        $scanned = php_ini_scanned_files();
        if ($scanned !== false && stripos($scanned, 'sqlsrv') !== false) {
            $extForwarded = true;
        }
    }

    // Case 3: Extensions were loaded via CLI -d flags or some other means.
    // Forward them explicitly.
    if (!$extForwarded) {
        $ext_dir = ini_get('extension_dir');
        if ($ext_dir !== false && $ext_dir !== '') {
            $php_cmd .= ' -d extension_dir=' . escapeshellarg($ext_dir);
        }
        if (extension_loaded('sqlsrv')) {
            $php_cmd .= ' -d extension=sqlsrv';
        }
        if (extension_loaded('pdo_sqlsrv')) {
            $php_cmd .= ' -d extension=pdo_sqlsrv';
        }
    }

    $php_cmd .= " " . escapeshellarg($worker_script);
    foreach ($args as $arg) {
        $php_cmd .= " " . escapeshellarg($arg);
    }

    // Redirect stderr: either to a specific file for diagnosis, or to
    // /dev/null (NUL on Windows) to suppress PHP startup warnings like
    // "Module already loaded" that would break --EXPECT-- comparison.
    if ($stderr_file !== null) {
        $php_cmd .= ' 2>' . escapeshellarg($stderr_file);
    } elseif (is_windows()) {
        $php_cmd .= ' 2>NUL';
    } else {
        $php_cmd .= ' 2>/dev/null';
    }

    $parts[] = $php_cmd;

    // On Windows use & as command separator; on Unix use &&
    $sep = is_windows() ? ' & ' : ' && ';
    return implode($sep, $parts);
}

/**
 * Kill any orphaned mock TDS server processes left behind by crashed tests.
 *
 * This is intended for use in --CLEAN-- sections, which run in a separate PHP
 * process and therefore cannot use stop_mock_tds_server() (no $mock handle).
 * It finds PID files left by start_mock_tds_server() and kills the processes.
 */
function cleanup_orphaned_mock_servers()
{
    $sep = DIRECTORY_SEPARATOR;
    $pattern = sys_get_temp_dir() . $sep . 'mock_tds_server_*.pid';
    foreach (glob($pattern) as $pidfile) {
        $pid = (int)@file_get_contents($pidfile);
        if ($pid > 0) {
            if (is_windows()) {
                // Verify it's a python process before killing
                $out = @shell_exec("tasklist /FI \"PID eq $pid\" /FO CSV /NH 2>NUL");
                if ($out !== null && stripos($out, 'python') !== false) {
                    exec("taskkill /F /T /PID $pid 2>NUL");
                }
            } else {
                // Verify it's actually a Python mock_tds_server before killing
                $cmdline = @file_get_contents("/proc/$pid/cmdline");
                if ($cmdline !== false && strpos($cmdline, 'mock_tds_server') !== false) {
                    @posix_kill($pid, 15); // SIGTERM
                    usleep(200000);
                    @posix_kill($pid, 9);  // SIGKILL
                }
            }
        }
        @unlink($pidfile);
    }
}

/**
 * Stop the mock TDS server and clean up.
 */
function stop_mock_tds_server($mock)
{
    if (isset($mock['pipes'])) {
        foreach ($mock['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }
    if (isset($mock['proc']) && is_resource($mock['proc'])) {
        if (is_windows()) {
            // On Windows, proc_terminate only kills the cmd.exe wrapper.
            // Use taskkill /T to kill the entire process tree.
            if (isset($mock['pid']) && $mock['pid'] > 0) {
                exec("taskkill /F /T /PID " . intval($mock['pid']) . " 2>NUL");
            }
        } else {
            proc_terminate($mock['proc'], 15); // SIGTERM
            $deadline = microtime(true) + 3;
            while (microtime(true) < $deadline) {
                $status = proc_get_status($mock['proc']);
                if (!$status['running']) break;
                usleep(50000);
            }
            $status = proc_get_status($mock['proc']);
            if ($status['running']) {
                proc_terminate($mock['proc'], 9); // SIGKILL
            }
        }
        proc_close($mock['proc']);
    }
    // Clean up PID file
    if (isset($mock['pidfile'])) {
        @unlink($mock['pidfile']);
    }
    // Clean up temp cert and log files
    if (isset($mock['tmpdir']) && is_dir($mock['tmpdir'])) {
        @unlink($mock['tmpdir'] . DIRECTORY_SEPARATOR . 'server.pem');
        @unlink($mock['tmpdir'] . DIRECTORY_SEPARATOR . 'server.key');
        @unlink($mock['tmpdir'] . DIRECTORY_SEPARATOR . 'server.log');
        @rmdir($mock['tmpdir']);
    }
}
?>
