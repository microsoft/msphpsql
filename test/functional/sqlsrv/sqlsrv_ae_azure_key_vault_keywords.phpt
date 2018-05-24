--TEST--
Test connection keywords for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('values.php');

// We will test the direct product (set of all possible combinations) of the following
$columnEncryption = ['enabled', 'disabled', 'notvalid', ''];
$keyStoreAuthentication = ['KeyVaultPassword', 'KeyVaultClientSecret', 'KeyVaultNothing', ''];
$keyStorePrincipalId = [$AKVPrincipalName, $AKVClientID, 'notaname', ''];
$keyStoreSecret = [$AKVPassword, $AKVSecret, 'notasecret', ''];

$is_win = (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN');

function checkErrors($errors, ...$codes)
{
    $codeFound = false;

    foreach ($codes as $code) {
        if ($code[0]==$errors[0][0] and $code[1]==$errors[0][1]) {
            $codeFound = true;
        }
    }

    if ($codeFound == false) {
        echo "Error: ";
        print_r($errors);
        echo "\nExpected: ";
        print_r($codes);
        echo "\n";
        fatalError("Error code not found.\n");
    }
}

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

$dataTypes = array("char($strsize)", "varchar($strsize)", "nvarchar($strsize)",
                    "decimal", "float", "real", "bigint", "int", "bit"
                    );

$tableName = "akv_comparison_table";

// Test every combination of the keywords above.
// Leave out good credentials to ensure that caching does not influence the
// results. The cache timeout can only be changed with SQLSetConnectAttr, so
// we can't run a PHP test without caching, and if we started with good
// credentials then subsequent calls with bad credentials can work, which
// would muddle the results of this test. Good credentials are tested in a
// separate test.
for ($i = 0; $i < sizeof($columnEncryption); ++$i) {
    for ($j = 0; $j < sizeof($keyStoreAuthentication); ++$j) {
        for ($k = 0; $k < sizeof($keyStorePrincipalId); ++$k) {
            for ($m = 0; $m < sizeof($keyStoreSecret); ++$m) {
                $connectionOptions = array("CharacterSet"=>"UTF-8",
                                           "database"=>$databaseName,
                                           "uid"=>$uid,
                                           "pwd"=>$pwd,
                                           "ConnectionPooling"=>0);

                if (!empty($columnEncryption[$i])) {
                    $connectionOptions['ColumnEncryption'] = $columnEncryption[$i];
                }
                if (!empty($keyStoreAuthentication[$j])) {
                    $connectionOptions['KeyStoreAuthentication'] = $keyStoreAuthentication[$j];
                }
                if (!empty($keyStorePrincipalId[$k])) {
                    $connectionOptions['KeyStorePrincipalId'] = $keyStorePrincipalId[$k];
                }
                if (!empty($keyStoreSecret[$m])) {
                    $connectionOptions['KeyStoreSecret'] = $keyStoreSecret[$m];
                }

                // Valid credentials getting skipped
                if (($i == 0 and $j == 0 and $k == 0 and $m == 0) or
                    ($i == 0 and $j == 1 and $k == 1 and $m == 1)) {
                    continue;
                }

                // Connect to the AE-enabled database
                // Failure is expected when the keyword combination is wrong
                $conn = sqlsrv_connect($server, $connectionOptions);
                if (!$conn) {
                    $errors = sqlsrv_errors();

                    checkErrors(
                        $errors,
                        array('08001','0'),
                        array('08001','-1'),    // SSL error occurs in Ubuntu
                        array('IMSSP','-110'),
                        array('IMSSP','-111'),
                        array('IMSSP','-112'),
                        array('IMSSP','-113')
                    );
                } else {
                    $columns = array();
                    $insertQuery = "";

                    // Generate the INSERT query
                    formulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

                    $stmt = AE\createTable($conn, $tableName, $columns);
                    if (!$stmt) {
                        fatalError("Failed to create table $tableName.\n");
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
                        fatalError("sqlsrv_prepare failed.\n");
                    }

                    // Execute the INSERT query
                    // This is where we expect failure if the credentials are incorrect
                    if (sqlsrv_execute($stmt) == false) {
                        $errors = sqlsrv_errors();

                        if (!AE\isColEncrypted()) {
                            checkErrors(
                                $errors,
                                array('CE258', '0'),
                                array('CE275', '0')
                            );
                        } else {
                            checkErrors(
                                $errors,
                                array('CE258', '0'),
                                array('CE275', '0'),
                                array('22018', '206')
                            );
                        }

                        sqlsrv_free_stmt($stmt);
                    } else {
                        // The INSERT query succeeded with bad credentials, which
                        // should only happen when encryption is not enabled.
                        if (AE\isColEncrypted()) {
                            fatalError("Successful insertion with bad credentials\n");
                        }
                    }

                    // Free the statement and close the connection
                    sqlsrv_close($conn);
                }
            }
        }
    }
}

echo "Done.\n";
?>
--EXPECT--
Done.
