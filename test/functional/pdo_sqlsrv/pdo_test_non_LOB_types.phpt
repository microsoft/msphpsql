--TEST--
Test reading non LOB types
--DESCRIPTION--
A simpler version of sqlsrv test "test_sqlsrv_phptype_stream.phpt" for reading from 
pre-populated tables [test_streamable_types] and [test_types]
--SKIPIF--
<?php require('skipif_azure_dw.inc'); ?>
--FILE--
<?php

require('MsSetup.inc');

function verifyResult($result)
{
    if (empty($result) || !is_array($result)) {
        echo "Result is empty or not an array!\n";
        return;
    }
    
    $trimmedLen = 200;
    $fullLen = 256;
    $input = str_repeat('A', $trimmedLen);
    for ($i = 0; $i < count($result); $i++) {
        $expectedLen = ($i % 2 == 0) ? $fullLen : $trimmedLen;
        $len = strlen($result[$i]);
        if ($len != $expectedLen) {
            echo "String length $len for column ". ($i + 1) . " is unexpected!\n";
        }
        
        $data = rtrim($result[$i]);
        if ($data !== $input) {
            echo "Result for column ". ($i + 1) . " is unexpected:";
            var_dump($result[$i]);
        }
    }
}

try {
    $conn = new PDO("sqlsrv:server=$server; Database = $databaseName", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // test the allowed non LOB column types
    $tsql = "SELECT [char_short_type], [varchar_short_type], [nchar_short_type], [nvarchar_short_type], [binary_short_type], [varbinary_short_type] FROM [test_streamable_types]";
    $stmt = $conn->query($tsql);

    $result = $stmt->fetch(PDO::FETCH_NUM);
    verifyResult($result);

    // test not streamable types
    // The size of a float is platform dependent, with a precision of roughly 14 digits
    // http://php.net/manual/en/language.types.float.php
    // For example, the input value for column [real_type] in setup\test_types.sql is 1.18E-38
    // but in some distros the fetched value is 1.1799999E-38
    $tsql = "SELECT * FROM [test_types]";
    $stmt = $conn->query($tsql);
    $result = $stmt->fetch(PDO::FETCH_NUM);
    print_r($result);

} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

unset($stmt);
unset($conn);

?>
--EXPECTREGEX--
Array
\(
    \[0\] => 9223372036854775807
    \[1\] => 2147483647
    \[2\] => 32767
    \[3\] => 255
    \[4\] => 1
    \[5\] => 9999999999999999999999999999999999999
    \[6\] => 922337203685477\.5807
    \[7\] => 214748\.3647
    \[8\] => 1\.79E\+308
    \[9\] => (1\.18E-38|1\.1799999E-38)
    \[10\] => 1968-12-12 16:20:00.000
    \[11\] => 
\)