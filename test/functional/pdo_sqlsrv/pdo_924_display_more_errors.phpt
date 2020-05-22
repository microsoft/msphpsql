--TEST--
GitHub issue 924 - Wrong error message after switching database context
--DESCRIPTION--
Verifies that the user has the option to see the following error message after the first one.
--SKIPIF--
<?php require('skipif_azure.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

$tsql = "SET NOCOUNT ON; USE $databaseName; SELECT 1/0 AS col1";

$errorInfo = array("01000", 5701, "*Changed database context to '$databaseName'.");
$errorInfo2 = array("22012", 8134, "*Divide by zero error encountered*");

function compareErrorInfo($actualErrorInfo, $errorInfo, $index = 0)
{
    if (($actualErrorInfo[$index] != $errorInfo[0]) || 
        ($actualErrorInfo[$index + 1] != $errorInfo[1]) ||
        !fnmatch($errorInfo[2], $actualErrorInfo[$index + 2])) {
        
        echo "Expected this: \n";
        var_dump($errorInfo);
        echo "Actual error info is: \n";
        var_dump($actualErrorInfo);
    }
}

function compare2ErrorInfo($actualErrorInfo)
{
    global $errorInfo, $errorInfo2;
    
    if (count($actualErrorInfo) != 6) {
        echo "Expect 6 elements in the error info!\n";
        var_dump($actualErrorInfo);
        return;
    }
    
    // Compare the first three elements
    compareErrorInfo($actualErrorInfo, $errorInfo);
    compareErrorInfo($actualErrorInfo, $errorInfo2, 3);
}

function checkException($conn, $on)
{
    global $tsql, $errorInfo, $errorInfo2;

    ini_set('pdo_sqlsrv.report_additional_errors', $on);

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare($tsql);
        $stmt->execute();
        
        var_dump($stmt->fetchColumn());
       
        echo "Exception should have been thrown!\n";
    } catch (PDOException $e) {
        // compare errorInfo arrays from both the exception object and the stmt object
        if ($on) {
            compare2ErrorInfo($e->errorInfo);
            compare2ErrorInfo($stmt->errorInfo());
        }
        else {
            compareErrorInfo($e->errorInfo, $errorInfo);
            compareErrorInfo($stmt->errorInfo(), $errorInfo);
        }
    }
    
    unset($stmt);
    unset($conn);
}

function checkWarning($conn, $on)
{
    global $tsql, $errorInfo, $errorInfo2;

    ini_set('pdo_sqlsrv.report_additional_errors', $on);

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $stmt = $conn->prepare($tsql);
        $stmt->execute();
        
        compareErrorInfo($stmt->errorInfo(), $errorInfo);
        if ($on) {
            compareErrorInfo($stmt->errorInfo(), $errorInfo2, 3);
        } else {
            echo count($stmt->errorInfo()) . PHP_EOL;
        }
    } catch (PDOException $e) {
        echo " Warnings are logged but do not expect exceptions.\n";
        var_dump($e);
    }
    
    unset($stmt);
    unset($conn);
}

try {
    // This forces PHP to log errors rather than displaying errors on screen
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    $logFilename = 'php_924_errors.log';
    $logFilepath = dirname(__FILE__).'/'.$logFilename;
    
    if (file_exists($logFilepath)) {
        unlink($logFilepath);
    }

    ini_set('error_log', $logFilepath);
    ini_set('pdo_sqlsrv.log_severity', '2');    // warnings only

    $conn = new PDO("sqlsrv:server=$server;", $uid, $pwd);
    checkWarning($conn, 1);
    checkException($conn, 1);
    checkWarning($conn, 0);
    checkException($conn, 0);
    
    if (file_exists($logFilepath)) {
        echo file_get_contents($logFilepath);
        unlink($logFilepath);
    } else {
        echo "Expected to find the log file\n";
    }
} catch (PDOException $e) {
    var_dump($e);
}

echo "\nDone\n";
?>
--EXPECTF--
3
[%s UTC] pdo_sqlsrv_db_handle_factory: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_db_handle_factory: error code = 5701
[%s UTC] pdo_sqlsrv_db_handle_factory: message = %s[SQL Server]Changed database context to 'master'.
[%s UTC] pdo_sqlsrv_db_handle_factory: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_db_handle_factory: error code = 5703
[%s UTC] pdo_sqlsrv_db_handle_factory: message = %s[SQL Server]Changed language setting to us_english.
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 5701
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Changed database context to '%s'.
[%s UTC] PHP Warning:  PDOStatement::execute(): SQLSTATE[01000]: Warning: 5701 %s[SQL Server]Changed database context to '%s'. in %spdo_924_display_more_errors.php on line %d
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 5701
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Changed database context to '%s'.
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 5701
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Changed database context to '%s'.
[%s UTC] PHP Warning:  PDOStatement::execute(): SQLSTATE[01000]: Warning: 5701 %s[SQL Server]Changed database context to '%s'. in %spdo_924_display_more_errors.php on line %d
[%s UTC] pdo_sqlsrv_stmt_execute: SQLSTATE = 01000
[%s UTC] pdo_sqlsrv_stmt_execute: error code = 5701
[%s UTC] pdo_sqlsrv_stmt_execute: message = %s[SQL Server]Changed database context to '%s'.

Done
