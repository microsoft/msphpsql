--TEST--
Test client ID/secret credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsSetup.inc");
require_once('values.php');

// Set up the columns and build the insert query. Each data type has an
// AE-encrypted and a non-encrypted column side by side in the table.
function formulateSetupQuery($tableName, &$dataTypes, &$columns, &$insertQuery)
{
    $columns = array();
    $queryTypes = "(";
    $queryTypesAE = "(";
    $valuesString = "VALUES (";
    $numTypes = sizeof($dataTypes);

    for ($i = 0; $i < $numTypes; ++$i) {
        // Replace parentheses for column names
        $colname = str_replace(array("(", ",", ")"), array("_", "_", ""), $dataTypes[$i]);
        $columns[] = new ColumnMeta($dataTypes[$i], "c_".$colname."_AE", null, "deterministic", false);
        $columns[] = new ColumnMeta($dataTypes[$i], "c_".$colname, null, "none", false);
        $queryTypes .= "c_"."$colname, ";
        $queryTypes .= "c_"."$colname"."_AE, ";
        $valuesString .= "?, ?, ";
    }

    $queryTypes = substr($queryTypes, 0, -2).")";
    $valuesString = substr($valuesString, 0, -2).")";

    $insertQuery = "INSERT INTO $tableName ".$queryTypes." ".$valuesString;
}

$strsize = 64;

$dataTypes = array("char($strsize)", "varchar($strsize)", "nvarchar($strsize)",
                    "decimal", "float", "real", "bigint", "int", "bit"
                    );

$tableName = "akv_comparison_table";

$connectionOptions = "sqlsrv:Server=$server;Database=$databaseName";

$connectionOptions .= ";ColumnEncryption=enabled";
$connectionOptions .= ";KeyStoreAuthentication=KeyVaultClientSecret";
$connectionOptions .= ";KeyStorePrincipalId=".$AKVClientID;
$connectionOptions .= ";KeyStoreSecret=".$AKVSecret;
$connectionOptions .= ";";

try {
    // Connect to the AE-enabled database
    $conn = new PDO($connectionOptions, $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columns = array();
    $insertQuery = "";

    // Generate the INSERT query
    formulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

    createTable($conn, $tableName, $columns);

    // Duplicate all values for insertion - one is encrypted, one is not
    $testValues = array();
    for ($n = 0; $n < sizeof($small_values); ++$n) {
        $testValues[] = $small_values[$n];
        $testValues[] = $small_values[$n];
    }

    // Prepare the INSERT query
    // This is never expected to fail
    $stmt = $conn->prepare($insertQuery);
    if ($stmt == false) {
        print_r($conn->errorInfo());
        fatalError("sqlsrv_prepare failed\n");
    }

    // Execute the INSERT query
    // This should not fail since our credentials are correct
    if ($stmt->execute($testValues) == false) {
        print_r($stmt->errorInfo());
        fatalError("INSERT query execution failed with good credentials.\n");
    } else {
        // Get the data back and compare encrypted and non-encrypted versions
        $selectQuery = "SELECT * FROM $tableName";

        $stmt1 = $conn->query($selectQuery);

        $data = $stmt1->fetchAll(PDO::FETCH_NUM);
        $data = $data[0];

        if (sizeof($data) != 2*sizeof($dataTypes)) {
            fatalError("Incorrect number of fields returned.\n");
        }

        for ($n = 0; $n < sizeof($data); $n += 2) {
            if ($data[$n] != $data[$n + 1]) {
                echo "Failed on field $n: ".$data[$n]." ".$data[$n + 1]."\n";
                fatalError("AE and non-AE values do not match.\n");
            }
        }

        echo "Successful insertion and retrieval with client ID/secret.\n";

        unset($stmt);
        unset($stmt1);
    }

    // Free the statement and close the connection
    unset($stmt);
    unset($conn);
} catch (Exception $e) {
    echo "Unexpected error.\n";
    print_r($e->errorInfo);
}

?>
--EXPECT--
Successful insertion and retrieval with client ID/secret.
