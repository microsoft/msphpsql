--TEST--
SQLSRV bounds credential brace validation for empty, quoted and escaped values
--DESCRIPTION--
An invalid Driver stops valid values before SQLDriverConnect. Run with Valgrind
to detect reads beyond the terminator of brace-quoted values.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
$values = array(
    array('', true), array('}', false), array('{', true), array('{}', true),
    array('{t}', true), array('{}}', false), array('}}', true), array('}}}', false),
    array('}}}}', true), array('{}}}', true), array('}{', false), array('}{{', false),
    array('test', true), array('{test}', true), array('{test', true), array('test}', false),
    array('{{test}}', false), array('{{test}', true), array('{{test', true),
    array('test}}', true), array('{test}}', false), array('test}}}', false),
    array('{test}}}', true), array('{test}}}}', false), array('{test}}}}}', true),
    array('te}st', false), array('{te}st}', false), array('{te}}st}', true),
    array('te}}s}t', false), array('te}}s}}t', true), array('te}}}}st', true)
);
foreach (array('PWD', 'Password') as $key) {
    foreach ($values as $index => $case) {
        $conn = sqlsrv_connect('127.0.0.1', array('UID' => 'alias-test', $key => $case[0], 'Driver' => 'AliasTestInvalidDriver'));
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        $expected = $case[1] ? -106 : -4;
        if ($conn !== false || !isset($errors[0]) || $errors[0]['code'] !== $expected) {
            echo "$key case $index: FAIL\n";
        }
        if ($conn !== false) {
            sqlsrv_close($conn);
        }
    }
}
echo "Done\n";
?>
--EXPECT--
Done