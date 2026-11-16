--TEST--
Azure Key Vault connection configuration handles long credentials without truncation or memory corruption.
--SKIPIF--
<?php
require('skipif.inc');
require_once('MsCommon.inc');
$conn = connect();
$info = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION);
unset($conn);
if ((int) $info['DriverVer'] < 17) {
    die('skip Azure Key Vault requires ODBC Driver 17 or later.');
}
?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
require_once('MsSetup.inc');

$lengths = array(1, 259, 260, 263, 264, 4096, 8192);
$credentials = array('KeyStorePrincipalId', 'KeyStoreSecret');
$options = array(
    'KeyStorePrincipalId' => '00000000-0000-0000-0000-000000000000',
    'KeyStoreSecret' => 'test-secret'
);

foreach (array('KeyVaultClientSecret', 'KeyVaultPassword') as $authentication) {
    foreach ($credentials as $credential) {
        foreach ($lengths as $length) {
            $case = $options;
            $case[$credential] = str_repeat('a', $length);
            $keywords = "ColumnEncryption=Enabled;ConnectionPooling=false;KeyStoreAuthentication=$authentication;";
            foreach ($case as $name => $value) {
                $keywords .= "$name=$value;";
            }
            $dsn = getDSN($server, $databaseName, $driver, $keywords, true);
            $conn = new PDO($dsn, $uid, $pwd);
            $stmt = $conn->query('SELECT 1');
            if ((int) $stmt->fetchColumn() !== 1) {
                die("Unexpected query result.\n");
            }
            unset($stmt, $conn);
        }
        echo "$authentication $credential: OK\n";
    }

    $keywords = "ColumnEncryption=Enabled;ConnectionPooling=false;KeyStoreAuthentication=$authentication;";
    foreach ($credentials as $credential) {
        $keywords .= "$credential=" . str_repeat('b', 8192) . ";";
    }
    $dsn = getDSN($server, $databaseName, $driver, $keywords, true);
    $conn = new PDO($dsn, $uid, $pwd);
    unset($conn);
    echo "$authentication both credentials: OK\n";
}
?>
--EXPECT--
KeyVaultClientSecret KeyStorePrincipalId: OK
KeyVaultClientSecret KeyStoreSecret: OK
KeyVaultClientSecret both credentials: OK
KeyVaultPassword KeyStorePrincipalId: OK
KeyVaultPassword KeyStoreSecret: OK
KeyVaultPassword both credentials: OK
