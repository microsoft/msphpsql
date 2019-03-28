--TEST--
GitHub issue 929 - able to change the language when connecting
--DESCRIPTION--
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
function verifyErrorContents($e)
{
    require_once('MsCommon_mid-refactor.inc');
    
    $code = $e->getCode();
    if ($code !== '42S22') {
        echo "Expected SQLSTATE 42S22\n";
        var_dump($code);
    }
    
    // The error message is different when testing against Azure DB / Data Warehouse
    // Use wildcard patterns for matching
    if (isSQLAzure()) {
        $expected = "*Invalid column name [\"']BadColumn[\"']\.";
    } else {
        $expected = "*Ungültiger Spaltenname [\"']BadColumn[\"']\.";
    }

    $message = $e->getMessage();
    if (!fnmatch($expected, $message)) {
        echo "Expected to find $expected in the error message\n";
        var_dump($message);
    }
    
}

require_once("MsSetup.inc");

try {
    $conn = new PDO("sqlsrv:server=$server;Language = German", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tsql = "SELECT *, BadColumn FROM sys.syslanguages";
    $conn->query($tsql);
    echo 'This should have failed!\n';
} catch (PDOException $e) {
    verifyErrorContents($e);
}

unset($conn);

echo "Done\n";
?>
--EXPECT--
Done