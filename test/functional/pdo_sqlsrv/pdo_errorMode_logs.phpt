--TEST--
Test different error modes. The queries will try to do a select on a non-existing table
--DESCRIPTION--
This is similar to pdo_errorMode.phpt but will display the contents of php 
error logs based on log severity.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function toConnect()
{
    require("MsSetup.inc");
    
    $dsn = getDSN($server, $databaseName, $driver);
    $conn = new PDO($dsn, $uid, $pwd);
    return $conn;
}

function testException($conn)
{
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    global $sql;
    try {
        $q = $conn->query($sql);
    } catch (Exception $e) {
        // do nothing
    }
}

function testWarning($conn)
{
    // This forces PHP to log errors rather than displaying errors 
    // on screen -- only required for PDO::ERRMODE_WARNING
    ini_set('display_errors', '0');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

    global $sql;
    $q = $conn->query($sql);
}

function runtests($severity)
{
    global $conn;
    
    $logFilename = 'php_errors' . $severity . '.log';
    $logFilepath = dirname(__FILE__).'/'.$logFilename;
    if (file_exists($logFilepath)) {
        unlink($logFilepath);
    }

    ini_set('error_log', $logFilepath);
    ini_set('pdo_sqlsrv.log_severity', $severity);

    if ($severity === '2' ) {
        testWarning($conn);
    } else {
        testException($conn);
    }

    if (file_exists($logFilepath)) {
        if ($severity == '0') {
            echo "$logFilepath should not exist\n";
        }
        echo file_get_contents($logFilepath);
        unlink($logFilepath);
    }
    
    // Now reset logging by disabling it
    ini_set('pdo_sqlsrv.log_severity', '0');
    echo "Done with $severity\n\n";
}

try {
    ini_set('log_errors', '1');
    ini_set('pdo_sqlsrv.log_severity', '0');

    $conn = toConnect();
    $sql = "SELECT * FROM temp_table";

    runtests('0');
    runtests('1');
    runtests('2');
    runtests('4');
    runtests('-1');
} catch (Exception $e) {
    var_dump($e);
}

?>
--EXPECTF--
Done with 0

[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 42S02
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 208
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Invalid object name 'temp_table'.
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 42000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 8180
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Statement(s) could not be prepared.
Done with 1

[%s UTC] PHP Warning:  PDO::query(): SQLSTATE[42S02]: Base table or view not found: 208 %s[SQL Server]Invalid object name 'temp_table'. in %spdo_errorMode_logs.php on line %d
Done with 2

[%s UTC] pdo_sqlsrv_stmt_dtor: entering
[%s UTC] pdo_sqlsrv_dbh_prepare: entering
[%s UTC] pdo_sqlsrv_stmt_execute: entering
Done with 4

[%s UTC] pdo_sqlsrv_stmt_dtor: entering
[%s UTC] pdo_sqlsrv_dbh_prepare: entering
[%s UTC] pdo_sqlsrv_stmt_execute: entering
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 42S02
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 208
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Invalid object name 'temp_table'.
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 42000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 8180
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Statement(s) could not be prepared.
Done with -1

