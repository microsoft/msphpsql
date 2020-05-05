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

        unset($stmt);
        unset($conn);
    } catch (PDOException $e) {
        if ($on) {
            compare2ErrorInfo($e->errorInfo);
        }
        else {
            compareErrorInfo($e->errorInfo, $errorInfo);
        }
    }
}

function checkWarning($conn)
{
    global $tsql, $errorInfo;

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $stmt = $conn->prepare($tsql);
        $stmt->execute();
        
        compareErrorInfo($stmt->errorInfo(), $errorInfo);
       
        unset($stmt);
        unset($conn);
    } catch (PDOException $e) {
        echo "Do not expect exception\n";
        var_dump($e);
    }
}

try {
    $conn = new PDO("sqlsrv:server=$server;", $uid, $pwd);
    checkWarning($conn);
    checkException($conn, 1);
    checkException($conn, 0);
    
} catch (PDOException $e) {
    var_dump($e);
}

echo "\nDone\n";
?>
--EXPECTREGEX--
Warning: PDOStatement::execute\(\): SQLSTATE\[01000\]: Warning: 5701 \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Changed database context to '.+'. in .+(\/|\\)pdo_924_display_more_errors.php on line [0-9]+

Done
