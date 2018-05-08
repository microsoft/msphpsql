--TEST--
Test connection keywords nad credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('tools.inc');
require_once('values.php');

// We will test the direct product (set of all possible combinations) of the following
$columnEncryption = ['enabled', 'disabled', 'notvalid', ''];
$keyStoreAuthentication = ['KeyVaultPassword', 'KeyVaultClientSecret', 'KeyVaultNothing', ''];
$keyStorePrincipalId = [$AKVPrincipalName, $AKVClientID, 'notaname', ''];
$keyStoreSecret = [$AKVPassword, $AKVSecret, 'notasecret', ''];

function checkErrors($errors, ...$codes)
{
    $errSize = empty($errors) ? 0 : sizeof($errors);
    if (2*$errSize < sizeof($codes)) fatalError("Errors and input codes do not match.\n");
    
    $i=0;
    foreach($codes as $code)
    {
        if ($i%2==0) {
            if ($errors[$i/2][0] != $code)
            {
                echo "Error: ";
                print_r($errors[$i/2][0]);
                echo "\nExpected: ";
                print_r($code);
                echo "\n";
                fatalError("Error codes do not match.\n");
            }
        } else if ($i%2==1) {
            if ($errors[$i/2][1] != $code)
            {
                echo "Error: ";
                print_r($errors[$i/2][1]);
                echo "\nExpected: ";
                print_r($code);
                echo "\n";
                fatalError("Error codes do not match.\n");
            }
        }
        ++$i;
    }
}

// Set up the columns and build the insert query. Each data type has an
// AE-encrypted and a non-encrypted column side by side in the table.
function FormulateSetupQuery($tableName, &$dataTypes, &$columns, &$insertQuery)
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

$tableName = "akv_comparison_table";

// Test every combination of the keywords above.
// Leave out good credentials to ensure that caching does not influence the 
// results. The cache timeout can only be changed with SQLSetConnectAttr, so
// we can't run a PHP test without caching, and if we started with good
// credentials then subsequent calls with bad credentials can work, which
// would muddle the results of this test. Good credentials are tested in a
// separate test.
for ($i=0; $i < sizeof($columnEncryption); ++$i) {
    for ($j=0; $j < sizeof($keyStoreAuthentication); ++$j) {
        for ($k=0; $k < sizeof($keyStorePrincipalId); ++$k) {
            for ($m=0; $m < sizeof($keyStoreSecret); ++$m) {
                $connectionOptions = array("CharacterSet"=>"UTF-8", 
                                           "database"=>$databaseName, 
                                           "uid"=>$uid, 
                                           "pwd"=>$pwd,
                                           "ConnectionPooling"=>0);
                
                if (!empty($columnEncryption[$i]))
                    $connectionOptions['ColumnEncryption'] = $columnEncryption[$i];
                if (!empty($keyStoreAuthentication[$j]))
                    $connectionOptions['KeyStoreAuthentication'] = $keyStoreAuthentication[$j];
                if (!empty($keyStorePrincipalId[$k]))
                    $connectionOptions['KeyStorePrincipalId'] = $keyStorePrincipalId[$k];                
                if (!empty($keyStoreSecret[$m]))
                    $connectionOptions['KeyStoreSecret'] = $keyStoreSecret[$m];
                
                // Valid credentials getting skipped
                if (($i==0 and $j==0 and $k==0 and $m==0) or 
                    ($i==0 and $j==1 and $k==1 and $m==1)) {
                    continue;
                }

                // Connect to the AE-enabled database
                // Failure is expected when the keyword combination is wrong
                $conn = sqlsrv_connect($server, $connectionOptions);
                if (!$conn) {
                    $errors = sqlsrv_errors();
                    
                    if ($j==2)
                        checkErrors($errors, 'IMSSP', '-110');
                    else if ($i==2)
                        checkErrors($errors, '08001', '0');
                    else if ($j==3)
                        checkErrors($errors, 'IMSSP', '-111');
                    else if ($k==3)
                        checkErrors($errors, 'IMSSP', '-112');
                    else if ($m==3)
                        checkErrors($errors, 'IMSSP', '-113');
                    else
                        fatalError("Connection failed, unexpected connection string.\n");
                } else {
                    $columns = array();
                    $insertQuery = "";

                    // Generate the INSERT query
                    FormulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

                    $stmt = AE\createTable($conn, $tableName, $columns);
                    if (!$stmt) {
                        fatalError("Failed to create table $tableName.\n");
                    }

                    // Duplicate all values for insertion - one is encrypted, one is not
                    $testValues = array();
                    for ($n=0; $n<sizeof($small_values); ++$n) {
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
                        
                        if ($i==0 and $j==3 and $k==3 and $m==3)
                            checkErrors($errors, 'CE258', '0', 'CE202', '0');
                        if ($i==0 and $j==3)
                            checkErrors($errors, 'CE258', '0', 'CE202', '0');
                        else if ($i==1 or $i==3)
                            checkErrors($errors, '22018', '206', '42000', '33514','42000', '8180');
                        else
                            checkErrors($errors, 'CE275', '0', 'CE275', '0', 'CE258', '0', 'CE202', '0');
                        
                        sqlsrv_free_stmt($stmt);
                    } else {
                        // The INSERT query succeeded with bad credentials
                        fatalError( "Successful insertion with bad credentials\n");
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
