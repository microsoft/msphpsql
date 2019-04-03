--TEST--
Test various connection errors with invalid attributes
--DESCRIPTION--
This is similar to sqlsrv sqlsrv_connStr.phpt such that invalid connection attributes or values used when connecting.
.phpt
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function invalidEncoding($binary)
{
    try {
        $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        $conn = connect("", $options);
        $attr = ($binary) ? PDO::SQLSRV_ENCODING_BINARY : 'gibberish';
        
        $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $attr);
        echo "Should have failed about an invalid encoding.\n";
    }  catch (PDOException $e) {
        $error = '*An invalid encoding was specified for SQLSRV_ATTR_ENCODING.';
        if ($e->getCode() !== "IMSSP" || !fnmatch($error, $e->getMessage())) {
            echo "invalidEncoding($binary)\n";
            var_dump($e->getMessage());
        }
    }
}

function invalidServer()
{
    global $uid, $pwd;
    
    // Test an invalid server name in UTF-8
    try {
        $invalid = pack("H*", "ffc0");
        $conn = new PDO("sqlsrv:server = $invalid;", $uid, $pwd);
        echo "Should have failed to connect to invalid server.\n";
    }  catch (PDOException $e) {
        $error = '*An error occurred translating the connection string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page*';
        if ($e->getCode() !== "IMSSP" || !fnmatch($error, $e->getMessage())) {
            echo "invalidServer\n";
            var_dump($e->getMessage());
        }
    }
}

function utf8APP()
{
    global $server, $uid, $pwd;
    try {
        // Use a UTF-8 name
        $app = pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
        $dsn = "APP = $app;";
        $conn = connect($dsn);
    } catch (PDOException $e) {
        echo "With APP in UTF8 it should not have failed!\n";
        var_dump($e->getMessage());
    }
}

function invalidCredentials($badPasswd)
{
    global $server, $database;
    
    $user = pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
    if ($badPasswd) {
        // Use invalid UTF-8 
        $passwd = pack('H*', 'c59ec6c0d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
        $code = "IMSSP";
        $error = "*An error occurred translating the connection string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page.*";
    } else {
        // Use valid UTF-8 
        $passwd = pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
        $code = "28000";
        $error = "*Login failed for user 'Şơмė śäოрŀề ΆŚĈĨİ-ť℮×ŧ'.*";
    }
    
    try {
        $conn = new PDO("sqlsrv:server = $server; database = $database;", $user, $passwd);
        echo "Should have failed to connect\n";
    } catch (PDOException $e) {
        if ($e->getCode() !== $code || !fnmatch($error, $e->getMessage())) {
            echo "invalidCredentials($badPasswd)\n";
            var_dump($e->getMessage());
        }
    }
}

try {
    invalidEncoding(false);
    invalidEncoding(true);
    invalidServer();
    utf8APP();
    invalidCredentials(false);
    invalidCredentials(true);
    
    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Done

