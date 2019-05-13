--TEST--
UTF-8 connection strings
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once('MsSetup.inc');

// Expected errors
$gibberishEncoding = array('IMSSP', '-48', "The encoding 'gibberish' is not a supported encoding for the CharacterSet connection option.");   
$binaryEncoding = array('IMSSP', '-48', "The encoding 'binary' is not a supported encoding for the CharacterSet connection option.");   
$utf16Error = array('IMSSP', '-47', "An error occurred translating the connection string to UTF-16: *");
$userLoginFailed = array('28000', '18456', "*Login failed for user *");

function connect($options=array())
{
    global $server, $uid, $pwd, $databaseName;
    
    if (!isset($options['UID']) && !isset($options['uid'])) {
        $options['uid'] = $uid;
    }
    if (!isset($options['pwd']) && !isset($options['PWD'])) {
        $options['pwd'] = $pwd;
    }
    if (!isset($options['Database'])) {
        $options['database'] = $databaseName;
    }
    return sqlsrv_connect($server, $options);
}

function checkErrors($expectedError)
{
    // On Windows one call returns two identical errors, so just take the first element
    $error = sqlsrv_errors()[0];
    if (!fnmatch($expectedError[0], $error[0]) || 
        !fnmatch($expectedError[1], $error[1]) || 
        !fnmatch($expectedError[2], $error[2])) {
        fatalError("Errors do not match!\n");
    }
}

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

// test an invalid encoding
$c = connect(array( 'CharacterSet' => 'gibberish' ));
if ($c !== false) {
    fatalError("Should have errored on an invalid encoding.");
}
checkErrors($gibberishEncoding);

$c = connect(array( 'CharacterSet' => SQLSRV_ENC_BINARY ));
if ($c !== false) {
    fatalError("Should have errored on an invalid encoding.");
}
checkErrors($binaryEncoding);

$c = connect(array( 'CharacterSet' => SQLSRV_ENC_CHAR ));
if ($c === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_close($c);

// test an invalid server name in UTF-8
$server_invalid = pack("H*", "ffc0");
$c = sqlsrv_connect($server_invalid, array( 'Database' => 'test', 'CharacterSet' => 'utf-8' ));
if ($c !== false) {
    fatalError("sqlsrv_connect(1) should have failed");
}
checkErrors($utf16Error);

// APP has a UTF-8 name
$c = connect(array(
    'App' => pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'CharacterSet' => 'utf-8' ));
if ($c === false) {
    die(print_r(sqlsrv_errors(), true));
}

$c = connect(array(
    'UID' => pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'PWD' => pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'CharacterSet' => 'utf-8' ));
if ($c !== false) {
    fatalError("sqlsrv_connect(3) should have failed");
}
checkErrors($userLoginFailed);

// invalid UTF-8 in the pwd
$c = connect(array(
    'UID' => pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'PWD' => pack('H*', 'c59ec6c0d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'CharacterSet' => 'utf-8' ));
if ($c !== false) {
    fatalError("sqlsrv_connect(4) should have failed");
}
checkErrors($utf16Error);

echo "Test succeeded.\n";

?>
--EXPECT--
Test succeeded.
