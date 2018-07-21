--TEST--
Test username/password credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif_not_akv.inc'); ?>
--FILE--
<?php
require_once('sqlsrv_ae_azure_key_vault_common.php');

// The array of data types corresponding to $small_values in values.php.
// SHORT_STRSIZE is defined in values.php as well.
$dataTypes = array("char(".SHORT_STRSIZE.")", "varchar(".SHORT_STRSIZE.")", "nvarchar(".SHORT_STRSIZE.")",
                    "decimal", "float", "real", "bigint", "int", "bit"
                    );

// Test data insertion and retrieval with username/password
// and client Id/client secret combinations.
$connectionOptions = array("CharacterSet"=>"UTF-8",
                           "database"=>$databaseName,
                           "uid"=>$uid,
                           "pwd"=>$pwd,
                           "ConnectionPooling"=>0);

$connectionOptions['ColumnEncryption'] = "enabled";
$connectionOptions['KeyStoreAuthentication'] = "KeyVaultPassword";
$connectionOptions['KeyStorePrincipalId'] = $AKVPrincipalName;
$connectionOptions['KeyStoreSecret'] = $AKVPassword;

$tableName = "akv_comparison_table";

// Connect to the AE-enabled database, insert the data, and verify
$conn = sqlsrv_connect($server, $connectionOptions);
if (!$conn) {
    $errors = sqlsrv_errors();
    fatalError("Connection failed while testing good credentials.\n");
} else {
    insertDataAndVerify($conn, $tableName, $dataTypes, $small_values);

    echo "Successful insertion and retrieval with username/password.\n";

    sqlsrv_close($conn);
}

?>
--EXPECT--
Successful insertion and retrieval with username/password.
