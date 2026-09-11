--TEST--
PDO DSN passwords and workstation aliases reach the server without replacing constructor credentials
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
require_once('MsSetup.inc');
require_once('MsCommon_mid-refactor.inc');

function verifyAliasConnection($keywords, $password, $expectedHost, $label)
{
    global $server, $uid, $databaseName, $driver;
    $dsn = getDSN($server, $databaseName, $driver, $keywords);
    try {
        $conn = new PDO($dsn, $uid, $password);
        $stmt = $conn->query('SELECT HOST_NAME()');
        $host = $stmt->fetchColumn();
        $stmt->closeCursor();
        unset($stmt, $conn);
        echo $label, ': ', $host === $expectedHost ? 'OK' : 'FAIL (host)', PHP_EOL;
    } catch (PDOException $e) {
        // A failure must not dump the DSN or constructor arguments.
        echo $label, ': FAIL (connect)', PHP_EOL;
    }
}

// Match the driver's existing credential quoting, preserving already escaped braces.
$dsnPassword = $pwd;
if (strlen($dsnPassword) < 2 || $dsnPassword[0] !== '{' || substr($dsnPassword, -1) !== '}') {
    $dsnPassword = '{' . $dsnPassword . '}';
}
foreach (array('PWD', 'Password', 'pAsSwOrD') as $key) {
    verifyAliasConnection("$key=$dsnPassword;WorkstationID=alias-test;ConnectTimeout=5", null, 'alias-test', $key);
}
verifyAliasConnection('Password=bad};WorkstationID=alias-test', $pwd, 'alias-test', 'constructor wins');
verifyAliasConnection("PWD=bad};Password=$dsnPassword;WSID=alias-test", null, 'alias-test', 'Password last');
verifyAliasConnection("Password=bad};PWD=$dsnPassword;WSID=alias-test", null, 'alias-test', 'PWD last');
verifyAliasConnection('WSID=old;WorkstationID=new', $pwd, 'new', 'WorkstationID last');
verifyAliasConnection('WorkstationID=old;WSID=new', $pwd, 'new', 'WSID last');
verifyAliasConnection('WorkstationID={alias;=}}test}', $pwd, 'alias;=}test', 'workstation delimiters');
// These check acceptance in both orders, not elapsed-time precedence.
verifyAliasConnection('LoginTimeout=2;ConnectTimeout=5;WSID=alias-test', $pwd, 'alias-test', 'ConnectTimeout last');
verifyAliasConnection('ConnectTimeout=2;LoginTimeout=5;WSID=alias-test', $pwd, 'alias-test', 'LoginTimeout last');
?>
--EXPECT--
PWD: OK
Password: OK
pAsSwOrD: OK
constructor wins: OK
Password last: OK
PWD last: OK
WorkstationID last: OK
WSID last: OK
workstation delimiters: OK
ConnectTimeout last: OK
LoginTimeout last: OK