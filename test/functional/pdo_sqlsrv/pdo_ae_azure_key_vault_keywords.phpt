--TEST--
Test connection keywords and credentials for Azure Key Vault for Always Encrypted.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsSetup.inc");
require_once('values.php');

// We will test the direct product (set of all possible combinations) of the following
$columnEncryption = ['enabled', 'disabled', 'notvalid', ''];
$keyStoreAuthentication = ['KeyVaultPassword', 'KeyVaultClientSecret', 'KeyVaultNothing', ''];
$keyStorePrincipalId = [$AKVPrincipalName, $AKVClientID, 'notaname', ''];
$keyStoreSecret = [$AKVPassword, $AKVSecret, 'notasecret', ''];

// Verify that the error is in the list of expected errors
function checkErrors($errors, ...$codes)
{
    $codeFound = false;
    
    foreach($codes as $code)
    {
        if ($code[0]==$errors[0] and $code[1]==$errors[1])
            $codeFound = true;
    }
    
    if ($codeFound == false)
    {
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
// If column encryption is not set in MsSetup.inc, this function simply
// creates two non-encrypted columns side-by-side for each type.
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
                $connectionOptions = "sqlsrv:Server=$server;Database=$databaseName";
                
                if (!empty($columnEncryption[$i]))
                    $connectionOptions .= ";ColumnEncryption=".$columnEncryption[$i];
                if (!empty($keyStoreAuthentication[$j]))
                    $connectionOptions .= ";KeyStoreAuthentication=".$keyStoreAuthentication[$j];
                if (!empty($keyStorePrincipalId[$k]))
                    $connectionOptions .= ";KeyStorePrincipalId=".$keyStorePrincipalId[$k];                
                if (!empty($keyStoreSecret[$m]))
                    $connectionOptions .= ";KeyStoreSecret=".$keyStoreSecret[$m];
                
                // Valid credentials getting skipped
                if (($i==0 and $j==0 and $k==0 and $m==0) or 
                    ($i==0 and $j==1 and $k==1 and $m==1)) {
                    continue;
                }

                $connectionOptions .= ";";

                try 
                {
                    // Connect to the AE-enabled database
                    $conn = new PDO($connectionOptions, $uid, $pwd);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $columns = array();
                    $insertQuery = "";

                    // Generate the INSERT query
                    FormulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

                    createTable($conn, $tableName, $columns);
                    
                    // Duplicate all values for insertion - one is encrypted, one is not
                    $testValues = array();
                    for ($n=0; $n<sizeof($small_values); ++$n) {
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
                    // Failure expected only if the keywords/credentials are wrong
                    if ($stmt->execute($testValues) == false) {
                        print_r($stmt->errorInfo());
                        $stmt = null;
                    } else {
                        // The INSERT query succeeded with bad credentials, which
                        // should only happen when encryption is not enabled.
                        if (isColEncrypted()) 
                            fatalError( "Successful insertion with bad credentials\n");
                    }
                                        
                    // Free the statement and close the connection
                    $stmt = null;
                    $conn = null;
                } 
                catch(Exception $e)
                {
                    $errors = $e->errorInfo;
                    
                    if (!isColEncrypted())
                    {
                        checkErrors($errors, array('CE258', '0'),  
                                             array('CE275', '0'),  
                                             array('IMSSP', '-85'),
                                             array('IMSSP', '-86'),
                                             array('IMSSP', '-87'),
                                             array('IMSSP', '-88'),
                                             array('08001', '0'), 
                                             array('08001', '-1'));  // SSL error occurs in Ubuntu
                    }
                    else
                    {
                        checkErrors($errors, array('CE258', '0'),  
                                             array('CE275', '0'),  
                                             array('IMSSP', '-85'),
                                             array('IMSSP', '-86'),
                                             array('IMSSP', '-87'),
                                             array('IMSSP', '-88'),
                                             array('08001', '0'),  
                                             array('08001', '-1'),   // SSL error occurs in Ubuntu
                                             array('22018', '206'));
                    }
                }
            }
        }
    }
}

echo "Done.\n";
?>
--EXPECT--
Done.
