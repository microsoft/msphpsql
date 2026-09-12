--TEST--
SQLSRV connection keyword aliases preserve validation and credential handling
--DESCRIPTION--
All cases fail before SQLDriverConnect: an unknown option, invalid Driver, or
credential validation stops the connection. No SQL Server is required.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
function expectAliasError($options, $code, $fragment, $label)
{
    $conn = sqlsrv_connect('127.0.0.1', $options);
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    $passed = $conn === false && isset($errors[0])
        && $errors[0]['SQLSTATE'] === 'IMSSP'
        && $errors[0]['code'] === $code
        && strpos($errors[0]['message'], $fragment) !== false;
    echo $label, ': ', $passed ? 'OK' : 'FAIL', PHP_EOL;
    if ($conn !== false) {
        sqlsrv_close($conn);
    }
}

$sentinel = '__AliasTestMustNotConnect';
$values = array(
    'LoginTimeout' => 3,
    'ConnectTimeout' => 3,
    'Failover_Partner' => 'unused',
    'FailoverPartner' => 'unused',
    'WSID' => 'alias-test',
    'WorkstationID' => 'alias-test',
    'PWD' => 'dummy',
    'Password' => 'dummy'
);
foreach ($values as $key => $value) {
    foreach (array($key, strtolower($key), strtoupper($key)) as $spelling) {
        expectAliasError(array($spelling => $value, $sentinel => true), -1, $sentinel, $spelling);
    }
}

foreach (array('LoginTimeout', 'ConnectTimeout') as $key) {
    expectAliasError(array($key => '3', $sentinel => true), -33, 'Integer type was expected', "$key type");
}
foreach (array('Failover_Partner', 'FailoverPartner', 'WSID', 'WorkstationID', 'PWD', 'Password') as $key) {
    expectAliasError(array($key => 3, $sentinel => true), -33, 'String type was expected', "$key type");
}
foreach (array('PWD', 'Password') as $key) {
    expectAliasError(array($key => null, $sentinel => true), -1, $key, "$key null");
    $password = 'dummy';
    $referenced = array($key => &$password, $sentinel => true);
    expectAliasError($referenced, -1, $sentinel, "$key reference");
    expectAliasError(array($key => "dummy\0suffix", $sentinel => true), -1, $key, "$key NUL");
    foreach (array('', 'dummy') as $value) {
        expectAliasError(array($key => $value, 'AccessToken' => 'dummy'), -115, 'Access Token', "$key token conflict");
    }
}

$base = array('UID' => 'alias-test', 'Driver' => 'AliasTestInvalidDriver');
expectAliasError($base + array('PWD' => 'bad}', 'Password' => 'dummy'), -106, 'Driver option', 'Password last');
expectAliasError($base + array('Password' => 'bad}', 'PWD' => 'dummy'), -106, 'Driver option', 'PWD last');
expectAliasError($base + array('PWD' => 'dummy', 'Password' => 'bad}'), -4, 'right brace', 'last password validated');
expectAliasError($base + array('Password' => '{dummy;=}}value}'), -106, 'Driver option', 'password delimiters');
expectAliasError(array('Passwrod' => 'dummy'), -1, 'Passwrod', 'unknown spelling');
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
LoginTimeout type: OK
ConnectTimeout type: OK
Failover_Partner type: OK
FailoverPartner type: OK
WSID type: OK
WorkstationID type: OK
PWD type: OK
Password type: OK
PWD null: OK
PWD reference: OK
PWD NUL: OK
PWD token conflict: OK
PWD token conflict: OK
Password null: OK
Password reference: OK
Password NUL: OK
Password token conflict: OK
Password token conflict: OK
Password last: OK
PWD last: OK
last password validated: OK
password delimiters: OK
unknown spelling: OK