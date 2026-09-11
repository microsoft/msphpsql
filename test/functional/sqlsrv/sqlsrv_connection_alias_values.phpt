--TEST--
SQLSRV password and workstation aliases reach the server with last-value precedence
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php
require('skipif.inc');
require('MsSetup.inc');
if (empty($uid)) {
    die('skip Requires SQL authentication');
}
?>
--FILE--
<?php
require_once('MsCommon.inc');

function verifyAliasConnection($options, $expectedHost, $label)
{
    global $server, $uid, $databaseName, $driver, $encrypt;
    $base = array('UID' => $uid, 'Database' => $databaseName, 'Driver' => $driver, 'Encrypt' => $encrypt);
    $conn = sqlsrv_connect($server, $base + $options);
    if ($conn === false) {
        echo $label, ': FAIL (connect)', PHP_EOL;
        return;
    }
    $stmt = sqlsrv_query($conn, 'SELECT HOST_NAME()');
    $host = null;
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        $host = $row[0] ?? null;
        sqlsrv_free_stmt($stmt);
    }
    echo $label, ': ', $host === $expectedHost ? 'OK' : 'FAIL (host)', PHP_EOL;
    sqlsrv_close($conn);
}

foreach (array('PWD', 'Password', 'pAsSwOrD') as $key) {
    verifyAliasConnection(array($key => $pwd, 'WorkstationID' => 'alias-test', 'ConnectTimeout' => 5), 'alias-test', $key);
}
verifyAliasConnection(array('PWD' => $pwd, 'WSID' => 'old', 'WorkstationID' => 'new'), 'new', 'WorkstationID last');
verifyAliasConnection(array('PWD' => $pwd, 'WorkstationID' => 'old', 'WSID' => 'new'), 'new', 'WSID last');
verifyAliasConnection(array('PWD' => $pwd, 'WorkstationID' => '{alias;=}}test}'), 'alias;=}test', 'workstation delimiters');
verifyAliasConnection(array('PWD' => 'bad}', 'Password' => $pwd, 'WSID' => 'alias-test'), 'alias-test', 'Password last');
verifyAliasConnection(array('Password' => 'bad}', 'PWD' => $pwd, 'WSID' => 'alias-test'), 'alias-test', 'PWD last');
// These check acceptance in both orders, not elapsed-time precedence.
verifyAliasConnection(array('PWD' => $pwd, 'LoginTimeout' => 2, 'ConnectTimeout' => 5, 'WSID' => 'alias-test'), 'alias-test', 'ConnectTimeout last');
verifyAliasConnection(array('PWD' => $pwd, 'ConnectTimeout' => 2, 'LoginTimeout' => 5, 'WSID' => 'alias-test'), 'alias-test', 'LoginTimeout last');
?>
--EXPECT--
PWD: OK
Password: OK
pAsSwOrD: OK
WorkstationID last: OK
WSID last: OK
workstation delimiters: OK
Password last: OK
PWD last: OK
ConnectTimeout last: OK
LoginTimeout last: OK