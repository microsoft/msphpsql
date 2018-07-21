--TEST--
Test client ID/secret credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif_not_akv.inc'); ?>
--FILE--
<?php
require_once('pdo_ae_azure_key_vault_common.php');

// The array of data types corresponding to $small_values in values.php.
// SHORT_STRSIZE is defined in values.php as well.
$dataTypes = array("char(".SHORT_STRSIZE.")", "varchar(".SHORT_STRSIZE.")", "nvarchar(".SHORT_STRSIZE.")",
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
