--TEST--
Test client ID/secret credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
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
        $columns[] = new AE\ColumnMeta($dataTypes[$i], "c_".$colname."_AE");
        $columns[] = new AE\ColumnMeta($dataTypes[$i], "c_".$colname, null, true, true);
        $queryTypes .= "c_"."$colname, ";
        $queryTypes .= "c_"."$colname"."_AE, ";
        $valuesString .= "?, ?, ";
    }

    $queryTypes = substr($queryTypes, 0, -2).")";
    $valuesString = substr($valuesString, 0, -2).")";

    $insertQuery = "INSERT INTO $tableName ".$queryTypes." ".$valuesString;
}

$strsize = 64;

$dataTypes = array ("char($strsize)", "varchar($strsize)", "nvarchar($strsize)",
                    "decimal", "float", "real", "bigint", "int", "bit"
                    );

// Test data insertion and retrieval with username/password
// and client Id/client secret combinations.
$connectionOptions = array("CharacterSet"=>"UTF-8",
                           "database"=>$databaseName,
                           "uid"=>$uid,
                           "pwd"=>$pwd,
                           "ConnectionPooling"=>0);

$tableName = "akv_comparison_table";

$connectionOptions['ColumnEncryption'] = "enabled";
$connectionOptions['KeyStoreAuthentication'] = "KeyVaultClientSecret";
$connectionOptions['KeyStorePrincipalId'] = $AKVClientID;
$connectionOptions['KeyStoreSecret'] = $AKVSecret;

// Connect to the AE-enabled database
$conn = sqlsrv_connect($server, $connectionOptions);
if (!$conn) {
    $errors = sqlsrv_errors();
    fatalError("Connection failed while testing good credentials.\n");
} else {
    $columns = array();
    $insertQuery = "";

    // Generate the INSERT query
    formulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    // Duplicate all values for insertion - one is encrypted, one is not
    $testValues = array();
    for ($n = 0; $n < sizeof($small_values); ++$n) {
        $testValues[] = $small_values[$n];
        $testValues[] = $small_values[$n];
    }

    // Prepare the INSERT query
    // This is never expected to fail
    $stmt = sqlsrv_prepare($conn, $insertQuery, $testValues);
    if ($stmt == false) {
        print_r(sqlsrv_errors());
        fatalError("sqlsrv_prepare failed\n");
    }

    // Execute the INSERT query
    // This should not fail since our credentials are correct
    if (sqlsrv_execute($stmt) == false) {
        $errors = sqlsrv_errors();
        fatalError("INSERT query execution failed with good credentials.\n");
    } else {
        // Get the data back and compare encrypted and non-encrypted versions
        $selectQuery = "SELECT * FROM $tableName";

        $stmt1 = sqlsrv_query($conn, $selectQuery);
        $data = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_NUMERIC);

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

        sqlsrv_free_stmt($stmt);
        sqlsrv_free_stmt($stmt1);
    }

    // Free the statement and close the connection
    sqlsrv_close($conn);
}

?>
--EXPECT--
Successful insertion and retrieval with client ID/secret.
