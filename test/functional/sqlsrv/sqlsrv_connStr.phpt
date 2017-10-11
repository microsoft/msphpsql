--TEST--
UTF-8 connection strings
--SKIPIF--
<?php require('skipif_unix.inc'); ?>
--FILE--
<?php

require_once('MsSetup.inc');
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

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

// test an invalid encoding
$c = connect(array( 'CharacterSet' => 'gibberish' ));
if ($c !== false) {
    fatalError("Should have errored on an invalid encoding.");
}
print_r(sqlsrv_errors());

$c = connect(array( 'CharacterSet' => SQLSRV_ENC_BINARY ));
if ($c !== false) {
    fatalError("Should have errored on an invalid encoding.");
}
print_r(sqlsrv_errors());

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
print_r(sqlsrv_errors());

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
print_r(sqlsrv_errors());

// invalid UTF-8 in the pwd
$c = connect(array(
    'UID' => pack('H*', 'c59ec6a1d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'PWD' => pack('H*', 'c59ec6c0d0bcc49720c59bc3a4e1839dd180c580e1bb8120ce86c59ac488c4a8c4b02dc5a5e284aec397c5a7'),
    'CharacterSet' => 'utf-8' ));
if ($c !== false) {
    fatalError("sqlsrv_connect(4) should have failed");
}
print_r(sqlsrv_errors());

echo "Test succeeded.\n";

?>
--EXPECTF--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -48
            [code] => -48
            [2] => The encoding 'gibberish' is not a supported encoding for the CharacterSet connection option.
            [message] => The encoding 'gibberish' is not a supported encoding for the CharacterSet connection option.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -48
            [code] => -48
            [2] => The encoding 'binary' is not a supported encoding for the CharacterSet connection option.
            [message] => The encoding 'binary' is not a supported encoding for the CharacterSet connection option.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -47
            [code] => -47
            [2] => An error occurred translating the connection string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page.

            [message] => An error occurred translating the connection string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page.

        )

)
Array
(
    [0] => Array
        (
            [0] => 28000
            [SQLSTATE] => 28000
            [1] => 18456
            [code] => 18456
            [2] => %SLogin failed for user '%s'.
            [message] => %SLogin failed for user '%s'.
        )

    [1] => Array
        (
            [0] => 28000
            [SQLSTATE] => 28000
            [1] => 18456
            [code] => 18456
            [2] => %SLogin failed for user '%s'.
            [message] => %SLogin failed for user '%s'.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -47
            [code] => -47
            [2] => An error occurred translating the connection string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page.

            [message] => An error occurred translating the connection string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page.

        )

)
Test succeeded.
