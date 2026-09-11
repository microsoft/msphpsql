--TEST--
PDO connection aliases and DSN passwords preserve constructor precedence
--DESCRIPTION--
An invalid option, invalid Driver, or credential validation stops each case
before SQLDriverConnect. Constructor passwords override DSN passwords whenever
non-null, including an empty string. No SQL Server is required.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
function expectAliasError($keywords, $username, $password, $code, $fragment, $label)
{
    try {
        $conn = new PDO('sqlsrv:Server=127.0.0.1;' . $keywords, $username, $password);
        echo $label, ': FAIL', PHP_EOL;
        unset($conn);
    } catch (PDOException $e) {
        $passed = isset($e->errorInfo[1]) && $e->errorInfo[0] === 'IMSSP'
            && $e->errorInfo[1] === $code && strpos($e->getMessage(), $fragment) !== false;
        // Never print the DSN, password, or exception trace on failure.
        echo $label, ': ', $passed ? 'OK' : 'FAIL', PHP_EOL;
    }
}

$sentinel = '__AliasTestMustNotConnect';
$values = array(
    'LoginTimeout' => '3',
    'ConnectTimeout' => '3',
    'Failover_Partner' => 'unused',
    'FailoverPartner' => 'unused',
    'WSID' => 'alias-test',
    'WorkstationID' => 'alias-test',
    'PWD' => 'dummy',
    'Password' => 'dummy'
);
foreach ($values as $key => $value) {
    foreach (array($key, strtolower($key), strtoupper($key)) as $spelling) {
        expectAliasError(" $spelling = $value;$sentinel=1", null, null, -42, $sentinel, $spelling);
    }
}

$driver = 'Driver=AliasTestInvalidDriver;';
expectAliasError($driver . 'Password=bad}', 'alias-test', 'dummy', -79, 'Driver option', 'constructor wins');
expectAliasError($driver . 'PWD=bad}', 'alias-test', '', -79, 'Driver option', 'empty constructor wins');
expectAliasError($driver . 'Password=dummy', 'alias-test', 'bad}', -21, 'right brace', 'constructor validated');
expectAliasError($driver . 'PWD=bad}', 'alias-test', null, -21, 'right brace', 'null constructor uses DSN');
expectAliasError($driver . 'PWD=bad};Password=dummy', 'alias-test', null, -79, 'Driver option', 'Password last');
expectAliasError($driver . 'Password=bad};PWD=dummy', 'alias-test', null, -79, 'Driver option', 'PWD last');
expectAliasError($driver . 'PWD=dummy;Password=bad}', 'alias-test', null, -21, 'right brace', 'last password validated');
expectAliasError($driver . 'Password={dummy;=}}value}', 'alias-test', null, -79, 'Driver option', 'password delimiters');
expectAliasError($driver . 'Password=', 'alias-test', null, -79, 'Driver option', 'empty DSN password');

foreach (array('PWD', 'Password') as $key) {
    foreach (array('', 'dummy') as $value) {
        expectAliasError("$key=$value;AccessToken=dummy", null, null, -90, 'Access Token', "$key token conflict");
    }
    expectAliasError("$key={dummy", null, null, -67, 'right brace', "$key missing brace");
    expectAliasError("$key={dummy}junk", null, null, -63, 'invalid value', "$key trailing junk");
}
expectAliasError('AccessToken=', null, null, -91, 'Access Token is empty', 'absent password');
expectAliasError('Passwrod=dummy', null, null, -42, 'Passwrod', 'unknown spelling');
?>
--EXPECT--
LoginTimeout: OK
logintimeout: OK
LOGINTIMEOUT: OK
ConnectTimeout: OK
connecttimeout: OK
CONNECTTIMEOUT: OK
Failover_Partner: OK
failover_partner: OK
FAILOVER_PARTNER: OK
FailoverPartner: OK
failoverpartner: OK
FAILOVERPARTNER: OK
WSID: OK
wsid: OK
WSID: OK
WorkstationID: OK
workstationid: OK
WORKSTATIONID: OK
PWD: OK
pwd: OK
PWD: OK
Password: OK
password: OK
PASSWORD: OK
constructor wins: OK
empty constructor wins: OK
constructor validated: OK
null constructor uses DSN: OK
Password last: OK
PWD last: OK
last password validated: OK
password delimiters: OK
empty DSN password: OK
PWD token conflict: OK
PWD token conflict: OK
PWD missing brace: OK
PWD trailing junk: OK
Password token conflict: OK
Password token conflict: OK
Password missing brace: OK
Password trailing junk: OK
absent password: OK
unknown spelling: OK