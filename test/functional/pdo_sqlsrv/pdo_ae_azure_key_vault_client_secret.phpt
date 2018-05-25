--TEST--
Test client ID/secret credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('pdo_ae_azure_key_vault_common.php');

$strsize = 64;

$dataTypes = array("char($strsize)", "varchar($strsize)", "nvarchar($strsize)",
                    "decimal", "float", "real", "bigint", "int", "bit"
                    );

$connectionOptions = "sqlsrv:Server=$server;Database=$databaseName";

$connectionOptions .= ";ColumnEncryption=enabled";
$connectionOptions .= ";KeyStoreAuthentication=KeyVaultClientSecret";
$connectionOptions .= ";KeyStorePrincipalId=".$AKVClientID;
$connectionOptions .= ";KeyStoreSecret=".$AKVSecret;
$connectionOptions .= ";";

$tableName = "akv_comparison_table";

// Connect to the AE-enabled database, insert the data, and verify
try {
    $conn = new PDO($connectionOptions, $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    insertDataAndVerify($conn, $tableName, $dataTypes, $small_values);

    echo "Successful insertion and retrieval with client ID/secret.\n";

    unset($conn);
} catch (Exception $e) {
    echo "Unexpected error.\n";
    print_r($e->errorInfo);
}

?>
--EXPECT--
Successful insertion and retrieval with client ID/secret.
