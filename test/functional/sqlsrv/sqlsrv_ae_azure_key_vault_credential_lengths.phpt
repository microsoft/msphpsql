--TEST--
Azure Key Vault connection configuration handles long credentials without truncation or memory corruption.
--SKIPIF--
<?php
require('skipif.inc');
require_once('MsCommon.inc');
$conn = connect();
$info = sqlsrv_client_info($conn);
sqlsrv_close($conn);
if ((int) $info['DriverVer'] < 17) {
    die('skip Azure Key Vault requires ODBC Driver 17 or later.');
}
?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('MsSetup.inc');

$lengths = array(1, 259, 260, 263, 264, 4096, 8192);
$credentials = array('KeyStorePrincipalId', 'KeyStoreSecret');
$options = array(
    'Database' => $databaseName,
    'UID' => $uid,
    'PWD' => $pwd,
    'ColumnEncryption' => 'Enabled',
    'ConnectionPooling' => false,
    'KeyStorePrincipalId' => '00000000-0000-0000-0000-000000000000',
    'KeyStoreSecret' => 'test-secret'
);

foreach (array('KeyVaultClientSecret', 'KeyVaultPassword') as $authentication) {
    $options['KeyStoreAuthentication'] = $authentication;
    foreach ($credentials as $credential) {
        foreach ($lengths as $length) {
            $case = $options;
            $case[$credential] = str_repeat('a', $length);
            $conn = connect($case);
            $stmt = sqlsrv_query($conn, 'SELECT 1');
            if ($stmt === false || sqlsrv_fetch($stmt) !== true || sqlsrv_get_field($stmt, 0) !== 1) {
                die("Unexpected query result.\n");
            }
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
        }
        echo "$authentication $credential: OK\n";
    }

    $case = $options;
    foreach ($credentials as $credential) {
        $case[$credential] = str_repeat('b', 8192);
    }
    $conn = connect($case);
    sqlsrv_close($conn);
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
