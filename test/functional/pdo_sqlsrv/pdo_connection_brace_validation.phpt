--TEST--
PDO bounds credential brace validation for constructor and DSN passwords
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
$dsn = 'sqlsrv:Server=127.0.0.1;Driver=AliasTestInvalidDriver;';
foreach ($values as $index => $case) {
    try {
        new PDO($dsn, 'alias-test', $case[0]);
        echo "constructor case $index: FAIL\n";
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) !== ($case[1] ? -79 : -21)) {
            echo "constructor case $index: FAIL\n";
        }
    }
}
foreach (array('PWD', 'Password') as $key) {
    foreach (array('{}', '{t}', '{}}}', '{test}}}', '{te}}st}', '{test;=value}') as $index => $value) {
        try {
            new PDO($dsn . "$key=$value", 'alias-test', null);
            echo "$key case $index: FAIL\n";
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== -79) {
                echo "$key case $index: FAIL\n";
            }
        }
    }
}
echo "Done\n";
?>
--EXPECT--
Done