--TEST--
Test simple logging with connection, simple query and then close
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function toConnect()
{
    require("MsSetup.inc");
    
    // Basic connection
    $dsn = getDSN($server, $databaseName, $driver);
    $conn = new PDO($dsn, $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $conn;
}

try {
    ini_set('log_errors', '1');

    $logFilename = 'php_errors.log';
    $logFilepath = dirname(__FILE__).'/'.$logFilename;
    if (file_exists($logFilepath)) {
        unlink($logFilepath);
    }
    
    ini_set('error_log', $logFilepath);
    ini_set('pdo_sqlsrv.log_severity', '-1');

    $conn = toConnect();
    $stmt = $conn->query("SELECT @@Version");
    
    // Ignore the fetch results
    $stmt->fetchAll();
    
    unset($conn);

    if (file_exists($logFilepath)) {
        echo file_get_contents($logFilepath);
        unlink($logFilepath);
    } else {
        echo "$logFilepath is missing!\n";
    }
    
    // Now reset logging by disabling it
    ini_set('pdo_sqlsrv.log_severity', '0');

    echo "Done\n";
} catch (Exception $e) {
    var_dump($e);
}

?>
--EXPECTF--
[%s UTC] pdo_sqlsrv_db_handle_factory: entering
[%s UTC] pdo_sqlsrv_db_handle_factory: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_db_handle_factory: error code = 5701
[%s UTC] pdo_sqlsrv_db_handle_factory: message = %s[SQL Server]Changed database context to '%s'.
[%s UTC] pdo_sqlsrv_db_handle_factory: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_db_handle_factory: error code = 5703
[%s UTC] pdo_sqlsrv_db_handle_factory: message = %s[SQL Server]Changed language setting to %s.
[%s UTC] pdo_sqlsrv_dbh_prepare: entering
[%s UTC] pdo_sqlsrv_stmt_execute: entering
[%s UTC] pdo_sqlsrv_stmt_describe_col: entering
[%s UTC] pdo_sqlsrv_stmt_fetch: entering
[%s UTC] pdo_sqlsrv_stmt_get_col_data: entering
[%s UTC] pdo_sqlsrv_stmt_fetch: entering
Done