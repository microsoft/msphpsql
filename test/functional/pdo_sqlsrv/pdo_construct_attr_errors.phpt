--TEST--
Test various connection errors with invalid attributes
--DESCRIPTION--
This is similar to sqlsrv sqlsrv_connStr.phpt such that invalid connection attributes or values used when connecting.
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
        if (!fnmatch($error, $e->getMessage())) {
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
        $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        $invalid = pack("H*", "ffc0");
        $conn = new PDO("sqlsrv:server = $invalid;", $uid, $pwd, $options);
        echo "Should have failed to connect to invalid server.\n";
    }  catch (PDOException $e) {
        $error1 = '*Login timeout expired';
        $error2 = '*An error occurred translating the connection string to UTF-16: *';
        if (fnmatch($error1, $e->getMessage()) || fnmatch($error2, $e->getMessage())) {
            ;   // matched at least one of the expected error messages 
        } else {
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

function invalidCredentials()
{
    global $server, $database;
    
    // Use valid UTF-8 
    $user = pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
    $passwd = pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
    
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    $error1 = "*Login failed for user \'*\'.";
    $error2 = "*Login timeout expired*";
    $error3 = "*Could not open a connection to SQL Server*";
    
    try {
        $conn = new PDO("sqlsrv:server = $server; database = $database;", $user, $passwd, $options);
        echo "Should have failed to connect\n";
    } catch (PDOException $e) {
        if (fnmatch($error1, $e->getMessage()) || 
            fnmatch($error2, $e->getMessage()) ||
            fnmatch($error3, $e->getMessage())) {
            ;   // matched at least one of the expected error messages 
        } else {
            echo "invalidCredentials()\n";
            var_dump($e->getMessage());
        }
    }
}

function invalidPassword()
{
    global $server, $database;
    
    // Use valid UTF-8
    $user = pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');
    // Use invalid UTF-8 
    $passwd = pack('H*', 'c59ec6c0d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7');

    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    
    // Possible errors
    $error = "*An error occurred translating the connection string to UTF-16: *";
    $error1 = "*Login failed for user \'*\'.";
    $error2 = "*Login timeout expired*";

    try {
        $conn = new PDO("sqlsrv:server = $server; database = $database;", $user, $passwd, $options);
        echo "Should have failed to connect\n";
    } catch (PDOException $e) {
        if (!fnmatch($error, $e->getMessage())) {
            // Sometimes it might fail with two other possible error messages
            if (fnmatch($error1, $e->getMessage()) || fnmatch($error2, $e->getMessage())) {
                ;   // matched at least one of the expected error messages 
            } else {
                echo "invalidPassword()\n";
                var_dump($e->getMessage());
            }
        }
    }
}

try {
    invalidEncoding(false);
    invalidEncoding(true);
    invalidServer();
    utf8APP();
    invalidCredentials();
    invalidPassword();
    
    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Done

