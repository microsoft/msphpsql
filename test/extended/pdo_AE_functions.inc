<?php

// runPlaintextTest is the main function that cycles through the 
// ColumnEncryption keywords, keys, and encryption types, testing 
// in-place plaintext encryption and rich computations. The arguments 
// all come from AE_v2_values.inc.
// Arguments:
// array    $ceValues: ColumnEncryption keywords/attestation URLs
// array    $keys: Encryption keys
// array    $encryptionTypes: Encryption types (Deterministic, Randomized)
// array    $targetCeValues: ColumnEncryption keywords/attestation URLs on reconnection
// array    $targetKeys: Encryption keys on reconnection
// array    $targetTypes: Encryption types on reconnection
// string   $tableName: Name of table used for testing
// array    $dataTypes: Data types going into the table
// array    $colNames: Plaintext column names
// array    $colNamesAE: Encrypted column names
// integer  $length: Size of string columns
// string   $slength: $length as a string
// array    $testValues: Data to be inserted into the table
// array    $comparisons: The comparison operators
// array    $patterns: Values to pattern match against
// array    $thresholds: Values to use comparison operators against
function runPlaintextTest($ceValues, $keys, $encryptionTypes, 
                          $targetCeValues, $targetKeys, $targetTypes, 
                          $tableName, $dataTypes, $colNames, $colNamesAE, 
                          $length, $slength, $testValues,
                          $comparisons, $patterns, $thresholds)
{
    // Create a table for each key and encryption type, re-encrypt using each
    // combination of target key and target encryption
    foreach ($ceValues as $attestationType=>$ceValue) {
        foreach ($keys as $key) {
            foreach ($encryptionTypes as $encryptionType) {

                // $count is used to ensure we only run testCompare and
                // testPatternMatch once for the initial table
                $count = 0;

                foreach ($targetCeValues as $targetAttestationType=>$targetCeValue) {
                    foreach ($targetKeys as $targetKey) {
                        foreach ($targetTypes as $targetType) {

                            $conn = connect($ceValue);
                            if (!$conn) {
                                if ($attestationType == 'invalid') {
                                    continue;
                                } else {
                                    die("Connection failed when it shouldn't have at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                                }
                            } elseif ($attestationType == 'invalid') {
                                die("Connection should have failed for invalid protocol at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                            }

                            // Free the encryption cache to avoid spurious 'operand type clash' errors
                            $conn->query("DBCC FREEPROCCACHE");

                            // Create and populate a non-encrypted table
                            $createQuery = constructCreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength);
                            $insertQuery = constructInsertQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                            try {
                                $stmt = $conn->query("DROP TABLE IF EXISTS $tableName");
                                $stmt = $conn->query($createQuery);
                            } catch(Exception $error) {
                                print_r($error);
                                die("Creating a plaintext table failed when it shouldn't have at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                            }

                            insertValues($conn, $insertQuery, $dataTypes, $testValues);

                            // Encrypt the table
                            // Split the data type array, because for some reason we get an error
                            // if the query is too long (>2000 characters)
                            // TODO: This is a known issue, follow up on it.
                            $splitdataTypes = array_chunk($dataTypes, 5);
                            foreach ($splitdataTypes as $split) {
                                $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $key, $encryptionType, $slength);
                                $isEncrypted = encryptTable($conn, $alterQuery, $key, $encryptionType, $attestationType);
                            }

                            // Test rich computations
                            if ($count == 0) {
                                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $attestationType, $isEncrypted);
                                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $attestationType, $isEncrypted);
                            }
                            ++$count;

                            // $sameKeyAndType is used when checking re-encryption, because no error is returned
                            $sameKeyAndType = false;
                            if (($key == $targetKey) and ($encryptionType == $targetType) and $isEncrypted) {
                                $sameKeyAndType = true;
                            }

                            // Disconnect and reconnect with the target ColumnEncryption keyword value
                            unset($conn);

                            $conn = connect($targetCeValue);
                            if (!$conn) {
                                if ($targetAttestationType == 'invalid') {
                                    continue;
                                } else {
                                    die("Connection failed when it shouldn't have at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                                }
                            } elseif ($targetAttestationType == 'invalid') {
                                continue;
                            }

                            testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $targetAttestationType, $isEncrypted);
                            testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $targetAttestationType, $isEncrypted);

                            // Re-encrypt the table
                            // Split the data type array, because for some reason we get an error
                            // if the query is too long (>2000 characters)
                            // TODO: This is a known issue, follow up on it.
                            $splitdataTypes = array_chunk($dataTypes, 5);
                            foreach ($splitdataTypes as $split) {
                                $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $targetKey, $targetType, $slength);
                                $encryptionSucceeded = encryptTable($conn, $alterQuery, $targetKey, $targetType, $targetAttestationType, $sameKeyAndType);
                            }

                            // Test rich computations
                            if ($encryptionSucceeded) {
                                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $targetKey, $targetType, $targetAttestationType,true);
                                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $targetKey, $targetType, $targetAttestationType, true);
                            } else {
                                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $targetAttestationType, $isEncrypted);
                                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $targetAttestationType, $isEncrypted);
                            }

                            unset($conn);
                        }
                    }
                }
            }
        }
    }
}

// runEncryptedTest is the main function that cycles through the 
// ColumnEncryption keywords, keys, and encryption types, testing 
// in-place re-encryption and rich computations. The arguments 
// all come from AE_v2_values.inc.
// Arguments:
// array    $ceValues: ColumnEncryption keywords/attestation URLs
// array    $keys: Encryption keys
// array    $encryptionTypes: Encryption types (Deterministic, Randomized)
// array    $targetCeValues: ColumnEncryption keywords/attestation URLs on reconnection
// array    $targetKeys: Encryption keys on reconnection
// array    $targetTypes: Encryption types on reconnection
// string   $tableName: Name of table used for testing
// array    $dataTypes: Data types going into the table
// array    $colNames: Plaintext column names
// array    $colNamesAE: Encrypted column names
// integer  $length: Size of string columns
// string   $slength: $length as a string
// array    $testValues: Data to be inserted into the table
// array    $comparisons: The comparison operators
// array    $patterns: Values to pattern match against
// array    $thresholds: Values to use comparison operators against
function runEncryptedTest($ceValues, $keys, $encryptionTypes, 
                          $targetCeValues, $targetKeys, $targetTypes, 
                          $tableName, $dataTypes, $colNames, $colNamesAE, 
                          $length, $slength, $testValues,
                          $comparisons, $patterns, $thresholds)
{
    // Create a table for each key and encryption type, re-encrypt using each
    // combination of target key and target encryption
    foreach ($ceValues as $attestationType=>$ceValue) {

        // Cannot create a table with encrypted data if CE is disabled
        // TODO: Since we can create an empty encrypted table with
        // CE disabled, account for the case where CE is disabled.
        if ($ceValue == 'disabled') continue;

        foreach ($keys as $key) {
            foreach ($encryptionTypes as $encryptionType) {

                // $count is used to ensure we only run testCompare and
                // testPatternMatch once for the initial table
                $count = 0;

                foreach ($targetCeValues as $targetAttestationType=>$targetCeValue) {
                    foreach ($targetKeys as $targetKey) {
                        foreach ($targetTypes as $targetType) {

                            $conn = connect($ceValue);
                            if (!$conn) {
                                if ($attestationType == 'invalid') {
                                    continue;
                                } else {
                                    die("Connection failed when it shouldn't have at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                                }
                            } elseif ($attestationType == 'invalid') {
                                die("Connection should have failed for invalid protocol at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                            }

                            // Free the encryption cache to avoid spurious 'operand type clash' errors
                            $conn->query("DBCC FREEPROCCACHE");

                            // Create and populate an encrypted table
                            $createQuery = constructAECreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength, $key, $encryptionType);
                            $insertQuery = constructInsertQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                            try {
                                $stmt = $conn->query("DROP TABLE IF EXISTS $tableName");
                                $stmt = $conn->query($createQuery);
                            } catch(Exception $error) {
                                print_r($error);
                                die("Creating an encrypted table failed when it shouldn't have at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                            }

                            $ceDisabled = ($attestationType == 'disabled') ? true : false;
                            insertValues($conn, $insertQuery, $dataTypes, $testValues, $ceDisabled);

                            $isEncrypted = true;

                            // Test rich computations
                            if ($count == 0) {
                                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $attestationType, $isEncrypted);
                                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $attestationType, $isEncrypted);
                            }
                            ++$count;

                            // $sameKeyAndType is used when checking re-encryption, because no error is returned
                            $sameKeyAndType = false;
                            if (($key == $targetKey) and ($encryptionType == $targetType) and $isEncrypted) {
                                $sameKeyAndType = true;
                            }

                            // Disconnect and reconnect with the target ColumnEncryption keyword value
                            unset($conn);

                            $conn = connect($targetCeValue);
                            if (!$conn) {
                                if ($targetAttestationType == 'invalid') {
                                    continue;
                                } else {
                                    die("Connection failed when it shouldn't have at ColumnEncryption = $ceValue, key = $key, type = $encryptionType, targets $targetCeValue, $targetKey, $targetType\n");
                                }
                            } elseif ($targetAttestationType == 'invalid') {
                                continue;
                            }

                            testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $targetAttestationType, $isEncrypted);
                            testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $targetAttestationType, $isEncrypted);

                            // Re-encrypt the table
                            $initiallyEnclaveEncryption = isEnclaveEnabled($key);

                            // Split the data type array, because for some reason we get an error
                            // if the query is too long (>2000 characters)
                            // TODO: This is a known issue, follow up on it.
                            $splitdataTypes = array_chunk($dataTypes, 5);
                            foreach ($splitdataTypes as $split) {
                                $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $targetKey, $targetType, $slength);
                                $encryptionSucceeded = encryptTable($conn, $alterQuery, $targetKey, $targetType, $targetAttestationType, $sameKeyAndType, true, $initiallyEnclaveEncryption);
                            }

                            // Test rich computations
                            if ($encryptionSucceeded) {
                                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $targetKey, $targetType, $targetAttestationType,true);
                                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $targetKey, $targetType, $targetAttestationType, true);
                            } else {
                                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $targetAttestationType, $isEncrypted);
                                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $targetAttestationType, $isEncrypted);
                            }

                            unset($conn);
                        }
                    }
                }
            }
        }
    }
}

// Connect and clear the procedure cache
function connect($attestationInfo)
{
    require("MsSetup.inc");
    $options = "sqlsrv:Server=$server;Database=$databaseName;ColumnEncryption=$attestationInfo";

    if ($keystore == 'akv') {

        $securityInfo = '';
        if ($AKVKeyStoreAuthentication == 'KeyVaultPassword') {
            $securityInfo .= ";KeyStoreAuthentication=$AKVKeyStoreAuthentication";
            $securityInfo .= ";KeyStorePrincipalId=$AKVPrincipalName";
            $securityInfo .= ";KeyStoreSecret=$AKVPassword";
        } elseif ($AKVKeyStoreAuthentication == 'KeyVaultClientSecret') {
            $securityInfo .= ";KeyStoreAuthentication=$AKVKeyStoreAuthentication";
            $securityInfo .= ";KeyStorePrincipalId=$AKVClientID";
            $securityInfo .= ";KeyStoreSecret=$AKVSecret";
        } else {
            die("Incorrect value for KeyStoreAuthentication keyword!\n");
        }

        $options .= $securityInfo;
    }

    try {
        $conn = new PDO($options, $uid, $pwd);
    } catch (PDOException $error) {
        $e = $error->errorInfo;
        checkErrors($e, array('CE400', '0'));
        return false;
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);

    // Check that enclave computations are enabled
    // See https://docs.microsoft.com/en-us/sql/relational-databases/security/encryption/configure-always-encrypted-enclaves?view=sqlallproducts-allversions#configure-a-secure-enclave
    $query = "SELECT [name], [value], [value_in_use] FROM sys.configurations WHERE [name] = 'column encryption enclave type';";
    
    $stmt = $conn->query($query);
    if (!$stmt) {
        print_r($conn->errorInfo());
        die("Error when checking if enclave computations are enabled. This should never happen! Non-HGS servers should have been skipped.\n");
    } else {
        $info = $stmt->fetch();
        if (empty($info) or ($info['value'] != 1) or ($info['value_in_use'] != 1)) {
            die("Error: enclave computations are not enabled on the server!");
        }
    }

    // Free the encryption cache to avoid spurious 'operand type clash' errors
    $conn->exec("DBCC FREEPROCCACHE");

    unset($stmt);
    
    return $conn;
}

// This CREATE TABLE query simply creates a non-encrypted table with
// two columns for each data type side by side
// This produces a query that looks like
// CREATE TABLE aev2test2 (
//     c_integer integer,
//     c_integer_AE integer
// )
function constructCreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength)
{
    $query = "CREATE TABLE ".$tableName." (\n    ";

    foreach ($dataTypes as $type) {
        if (dataTypeIsString($type)) {
            $query = $query.$colNames[$type]." ".$type."(".$slength."), \n    ";
            $query = $query.$colNamesAE[$type]." ".$type."(".$slength."), \n    ";
        } else {
            $query = $query.$colNames[$type]." ".$type.", \n    ";
            $query = $query.$colNamesAE[$type]." ".$type.", \n    ";
        }
    }

    // Remove the ", \n    " from the end of the query or the comma will cause a syntax error
    $query = substr($query, 0, -7)."\n)";

    return $query;
}

// The ALTER TABLE query encrypts columns. Each ALTER COLUMN directive must
// be preceded by ALTER TABLE
// This produces a query that looks like
// ALTER TABLE [dbo].[aev2test2]
//     ALTER COLUMN [c_integer_AE] integer
//     ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = [CEK-win-enclave], ENCRYPTION_TYPE = Randomized, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NOT NULL
//     WITH
//     (ONLINE = ON); ALTER TABLE [dbo].[aev2test2]
//     ALTER COLUMN [c_bigint_AE] bigint
//     ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = [CEK-win-enclave], ENCRYPTION_TYPE = Randomized, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NOT NULL
//     WITH
//     (ONLINE = ON); ALTER DATABASE SCOPED CONFIGURATION CLEAR PROCEDURE_CACHE;
function constructAlterQuery($tableName, $colNames, $dataTypes, $key, $encryptionType, $slength)
{
    $query = '';

    foreach ($dataTypes as $dataType) {
        $plength = dataTypeIsString($dataType) ? "(".$slength.")" : "";
        $collate = dataTypeNeedsCollate($dataType) ? " COLLATE Latin1_General_BIN2" : "";
        $query = $query." ALTER TABLE [dbo].[".$tableName."]
                          ALTER COLUMN [".$colNames[$dataType]."] ".$dataType.$plength." ".$collate."
                          ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = [".$key."], ENCRYPTION_TYPE = ".$encryptionType.", ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NOT NULL
                          WITH
                          (ONLINE = ON);";
    }

    $query = $query." ALTER DATABASE SCOPED CONFIGURATION CLEAR PROCEDURE_CACHE;";

    return $query;
}

// This CREATE TABLE query creates a table with two columns for
// each data type side by side, one plaintext and one encrypted
// This produces a query that looks like
// CREATE TABLE aev2test2 (
//     c_integer integer NULL,
//     c_integer_AE integer
//       COLLATE Latin1_General_BIN2   ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = [CEK-win-enclave], ENCRYPTION_TYPE = Randomized, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NULL
// )
function constructAECreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength, $key, $encryptionType)
{
    $query = "CREATE TABLE ".$tableName." (\n    ";

    foreach ($dataTypes as $type) {
        $collate = dataTypeNeedsCollate($type) ? " COLLATE Latin1_General_BIN2" : "";

        if (dataTypeIsString($type)) {
            $query = $query.$colNames[$type]." ".$type."(".$slength.") NULL, \n    ";
            $query = $query.$colNamesAE[$type]." ".$type."(".$slength.") \n    ";
            $query = $query."  ".$collate." ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = [".$key."], ENCRYPTION_TYPE = ".$encryptionType.", ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NULL,\n    ";
        } else {
            $query = $query.$colNames[$type]." ".$type." NULL, \n    ";
            $query = $query.$colNamesAE[$type]." ".$type." \n    ";
            $query = $query."  ".$collate." ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = [".$key."], ENCRYPTION_TYPE = ".$encryptionType.", ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NULL,\n    ";
        }
    }

    // Remove the ",\n    " from the end of the query or the comma will cause a syntax error
    $query = substr($query, 0, -6)."\n)";

    return $query;
}

// The INSERT query for the table
function constructInsertQuery($tableName, &$dataTypes, &$colNames, &$colNamesAE)
{
    $queryTypes = "(";
    $valuesString = "VALUES (";

    foreach ($dataTypes as $type) {
        $colName1 = $colNames[$type].", ";
        $colName2 = $colNamesAE[$type].", ";
        $queryTypes .= $colName1;
        $queryTypes .= $colName2;
        $valuesString .= "?, ?, ";
    }

    // Remove the ", " from the end of the query or the comma will cause a syntax error
    $queryTypes = substr($queryTypes, 0, -2).")";
    $valuesString = substr($valuesString, 0, -2).")";

    $insertQuery = "INSERT INTO $tableName ".$queryTypes." ".$valuesString;

    return $insertQuery;
}

function insertValues($conn, $insertQuery, $dataTypes, $testValues, $ceDisabled=false)
{
    if (empty($testValues)) {
        die("$testValues is empty or non-existent. Please check the required values file.\n");
    }
    
    for ($v = 0; $v < sizeof($testValues['bigint']); ++$v) {
        $insertValues = array();

        // Insert the data using PDO::prepare()
        try {
            $stmt = $conn->prepare($insertQuery);
            $i=1;
            foreach ($dataTypes as $type) {
                $PDOType = getPDOType($type);
                if (!dataTypeIsBinary($type)) {
                    $stmt->bindParam($i, $testValues[$type][$v], $PDOType);
                    $stmt->bindParam($i+1, $testValues[$type][$v], $PDOType);
                } else {
                    // unset() is necessary because otherwise the same data may be
                    // inserted into multiple binary columns.
                    unset($val);
                    $val=pack('H*', $testValues[$type][$v]);
                    $stmt->bindParam($i, $val, $PDOType, 0, PDO::SQLSRV_ENCODING_BINARY);
                    $stmt->bindParam($i+1, $val, $PDOType, 0, PDO::SQLSRV_ENCODING_BINARY);
                }
                $i+=2;
            }
            $stmt->execute();
        } catch (PDOException $error) {
            if (!$ceDisabled) {
                print_r($error);
                die("Inserting values in encrypted table failed\n");
            } else {
                $e = $error->errorInfo;
                checkErrors($e, array('22018', '206'));
            }
        }
    }

    unset($stmt);
}

// encryptTable attempts to encrypt the table in place and verifies
// if it works given the attestation info and key type.
// Arguments:
// resource $conn: The connection
// string   $alterQuery: The query to encrypt the table
// array    $thresholds: Values to use comparison operators against, from AE_v2_values.inc
// string   $key: Name of the encryption key
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', 'disabled', or 'wrongurl'
// bool     $sameKeyAndType: Whether the key and encryption type are same for re-encrypting
//                           as for initial encryption.
// bool     $initialEncryption: Whether we are testing with table initially encrypted, instead
//                              of plaintext being encrypted after creation
// bool     $initiallyEnclaveEncrypted: Whether the table was initally encrypted with an
//                                      enclave-enabled key
function encryptTable($conn, $alterQuery, $key, $encryptionType, $attestation, $sameKeyAndType=false, $initialEncryption=false, $initallyEnclaveEncrypted=false)
{
    try {
        $stmt = $conn->query($alterQuery);
        if ((!isEnclaveEnabled($key) or $attestation != 'correct') and !$sameKeyAndType) {
            die("Encrypting should have failed with attestation $attestation, key $key and encryption type $encryptionType\n");
        }
    } catch (PDOException $error) {
        if ($sameKeyAndType) {
            print_r($error);
            die("Encrypting table should not fail when target encryption key and type are the same as source: attestation $attestation, key $key and encryption type $encryptionType\n");
        } elseif ($initialEncryption and !$initallyEnclaveEncrypted) {
            $e = $error->errorInfo;
            checkErrors($e, array('42000', '33543'));
        } elseif ($attestation == 'correct') {
            if (isEnclaveEnabled($key)) {
                print_r($error);
                die("Encrypting with correct attestation failed when it shouldn't have: attestation $attestation, key $key and encryption type $encryptionType\n");
            } else {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33543'));
            }
        } elseif ($attestation == 'enabled' or $attestation == 'disabled') {
            if (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33546'));
            } else {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33543'));
            }
        } elseif ($attestation == 'wrongurl') {
            if (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('CE405', '0'));
            } else {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33543'));
            }
        } elseif ($attestation == 'invalid') {
            die("Encrypting table with invalid protocol! Should not get here!\n");
        } else {
            die("Error! This is no-man's-land\n");
        }

        return false;
    }

    unset($stmt);

    return true;
}

// compareResults checks that the results between the encrypted and non-encrypted
// columns are identical if statement execution succeeds. If statement execution
// fails, this function checks for the correct error.
// Arguments:
// statement $AEstmt: Prepared statement fetching encrypted data
// statement $nonAEstmt: Prepared statement fetching non-encrypted data
// string   $key: Name of the encryption key
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', or 'wrongurl'
// string   $comparison: Comparison operator
// string   $type: Data type the comparison is operating on
function compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $isEncrypted, $comparison='', $type='')
{
    try {
        $nonAEstmt->execute();
    } catch(Exception $error) {
        print_r($error);
        die("Executing non-AE computation statement failed!\n");
    }

    try {
        $AEstmt->execute();
    } catch(Exception $error) {
        if (!$isEncrypted) {
            die("Computation statement execution should not have failed for an unencrypted table: attestation $attestation, key $key and encryption type $encryptionType\n");
        }

        if ($attestation == 'enabled') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r($error);
                    die("Equality comparison failed for deterministic encryption: attestation $attestation, key $key and encryption type $encryptionType\n");
                } else {
                    $e = $error->errorInfo;
                    checkErrors($e, array('42000', '33277'));
                }
            } elseif (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33546'));
            } elseif (!isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } else {
                print_r($error);
                die("AE statement execution failed when it shouldn't: attestation $attestation, key $key and encryption type $encryptionType");
            }
        } elseif ($attestation == 'wrongurl') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    $e = $error->errorInfo;
                    die("Equality comparison failed for deterministic encryption: attestation $attestation, key $key and encryption type $encryptionType\n");
                } else {
                    $e = $error->errorInfo;
                    checkErrors($e, array('42000', '33277'));
                }
            } elseif (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('CE405', '0'));
            } elseif (!isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } else {
                print_r($error);
                die("AE statement execution failed when it shouldn't: attestation $attestation, key $key and encryption type $encryptionType");
            }
        } elseif ($attestation == 'correct') {
            if (!isEnclaveEnabled($key) and $encryptionType == 'Randomized') {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } elseif ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r($error);
                    die("Equality comparison failed for deterministic encryption: attestation $attestation, key $key and encryption type $encryptionType\n");
                } else {
                    $e = $error->errorInfo;
                    checkErrors($e, array('42000', '33277'));
                }
            } else {
                print_r($error);
                die("Comparison failed for correct attestation when it shouldn't have: attestation $attestation, key $key and encryption type $encryptionType\n");
            }
        } elseif ($attestation == 'disabled') {
            if (!isEnclaveEnabled($key) and $encryptionType == 'Randomized') {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } elseif ($comparison == '=' or $comparison == '<>' or $encryptionType == 'Randomized') {
                $e = $error->errorInfo;
                checkErrors($e, array('22018', '206'));
            } else {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            }
        } else {
            print_r($error);
            die("Unexpected error occurred in compareResults: attestation $attestation, key $key and encryption type $encryptionType\n");
        }

        return;
    }

    $AEres = $AEstmt->fetchAll(PDO::FETCH_NUM);
    $nonAEres = $nonAEstmt->fetchAll(PDO::FETCH_NUM);
    $AEcount = count($AEres);
    $nonAEcount = count($nonAEres);

    if ($type == 'char' or $type == 'nchar' or $type == 'binary') {
        // char and nchar may not return the same results - at this point
        // we've verified that statement execution works so just return
        // TODO: Check if this bug is fixed and if so, remove this if block
        return;
    } elseif ($AEcount > $nonAEcount) {
        print_r("Too many AE results for operation $comparison and data type $type!\n");
        print_r($AEres);
        print_r($nonAEres);
    } elseif ($AEcount < $nonAEcount) {
        print_r("Too many non-AE results for operation $comparison and data type $type!\n");
        print_r($AEres);
        print_r($nonAEres);
    } else {
        if ($AEcount != 0) {
            $i = 0;
            foreach ($AEres as $AEr) {
                if ($AEr[0] != $nonAEres[$i][0]) {
                    print_r("AE and non-AE results are different for operation $comparison and data type $type! For field $i, got AE result ".$AEres[$i][0]." and non-AE result ".$nonAEres[$i][0]."\n");
                }
                ++$i;
            }
        }
    }
}

// testCompare selects based on a comparison in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
// Arguments:
// resource $conn: The connection
// string   $tableName: Table name
// array    $comparisons: Comparison operations from AE_v2_values.inc
// array    $dataTypes: Data types from AE_v2_values.inc
// array    $colNames: Column names
// array    $thresholds: Values to use comparison operators against, from AE_v2_values.inc
// string   $key: Name of the encryption key
// integer  $length: Length of the string types, from AE_v2_values.inc
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', or 'wrongurl'
// bool     $isEncrypted: Whether the table is encrypted
function testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $attestation, $isEncrypted)
{
    foreach ($comparisons as $comparison) {
        foreach ($dataTypes as $type) {

            // Unicode operations with AE require the Latin1_General_BIN2
            // collation. If the COLLATE clause is left out, we get different
            // results between the encrypted and non-encrypted columns (probably
            // because the collation was only changed in the encryption query).
            $string = dataTypeIsStringMax($type);
            $collate = $string ? " COLLATE Latin1_General_BIN2" : "";
            $unicode = dataTypeIsUnicode($type);
            $PDOType = getPDOType($type);
            unset($threshold);
            $threshold = dataTypeIsBinary($type) ? pack('H*', $thresholds[$type]) : $thresholds[$type];

            $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE ".$comparison." ?".$collate;
            $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." ".$comparison." ?".$collate;

            try {
                $AEstmt = $conn->prepare($AEQuery);
                $nonAEstmt = $conn->prepare($nonAEQuery);

                if (!dataTypeIsBinary($type)) {
                    $AEstmt->bindParam(1, $threshold, $PDOType);
                    $nonAEstmt->bindParam(1, $threshold, $PDOType);
                } else {
                    $AEstmt->bindParam(1, $threshold, $PDOType, 0, PDO::SQLSRV_ENCODING_BINARY);
                    $nonAEstmt->bindParam(1, $threshold, $PDOType, 0, PDO::SQLSRV_ENCODING_BINARY);
                }
            } catch (PDOException $error) {
                print_r($error);
                die("Preparing/binding statements for comparison failed! Comparison $comparison, type $type");
            }

            compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $isEncrypted, $comparison, $type);

            unset($AEstmt);
            unset($nonAEstmt);
        }
    }
}

// testPatternMatch selects based on a pattern in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
// Arguments:
// resource $conn: The connection
// string   $tableName: Table name
// array    $patterns: Strings to pattern match, from AE_v2_values.inc
// array    $dataTypes: Data types from AE_v2_values.inc
// array    $colNames: Column names
// string   $key: Name of the encryption key
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', 'disabled', or 'wrongurl'
// bool     $isEncrypted: Whether the table is encrypted
function testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $attestation, $isEncrypted)
{
    foreach ($dataTypes as $type) {

        // TODO: Pattern matching doesn't work in AE for non-string types.
        // This is for security reasons, follow up on it.
        if (!dataTypeIsStringMax($type)) {
            continue;
        }

        foreach ($patterns[$type] as $pattern) {

            $patternArray = array($pattern,
                                  $pattern."%",
                                  "%".$pattern,
                                  "%".$pattern."%",
                                  );

            foreach ($patternArray as $spattern) {

                // Unicode operations with AE require the PHPTYPE to be specified as
                // UTF-8 and the Latin1_General_BIN2 collation. If the COLLATE
                // clause is left out, we get different results between the
                // encrypted and non-encrypted columns (probably because the
                // collation was only changed in the encryption query).
                // We must pass the length of the pattern matching string
                // to the SQLTYPE instead of the field size, as we usually would,
                // because otherwise we would get an empty result set.
                // We need iconv_strlen to return the number of characters
                // for unicode strings, since strlen returns the number of bytes.
                $unicode = dataTypeIsUnicode($type);
                $collate = $unicode ? " COLLATE Latin1_General_BIN2" : "";
                $PDOType = getPDOType($type);

                $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE LIKE ?".$collate;
                $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." LIKE ?".$collate;

                // TODO: Add binary type support below. May need to use unset()
                // as in insertValues().
                try {
                    $AEstmt = $conn->prepare($AEQuery);
                    $AEstmt->bindParam(1, $spattern, $PDOType);
                    $nonAEstmt = $conn->prepare($nonAEQuery);
                    $nonAEstmt->bindParam(1, $spattern, $PDOType);
                } catch (PDOException $error) {
                    print_r($error);
                    die("Preparing/binding statements for comparison failed! Comparison $comparison, type $type\n");
                }

                compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $isEncrypted, $pattern, $type);

                unset($AEstmt);
                unset($nonAEstmt);
            }
        }
    }
}

// Check that the expected errors ($codes) is found in the PDOException ($errors)
function checkErrors($errors, ...$codes)
{
    $codeFound = false;

    foreach ($codes as $code) {
        if ($code[0]==$errors[0] and $code[1]==$errors[1]) {
            $codeFound = true;
            break;
        }
    }

    if ($codeFound == false) {
        echo "Error: ";
        print_r($errors);
        echo "\nExpected: ";
        print_r($codes);
        echo "\n";
        die("Error code not found.\n");
    }
}

function isEnclaveEnabled($key)
{
    return (strpos($key, '-enclave') !== false);
}

function dataTypeIsString($dataType)
{
    return (in_array($dataType, ["binary", "varbinary", "char", "nchar", "varchar", "nvarchar"]));
}

function dataTypeIsStringMax($dataType)
{
    return (in_array($dataType, ["char", "nchar", "varchar", "nvarchar", "varchar(max)", "nvarchar(max)"]));
}

function dataTypeNeedsCollate($dataType)
{
    return (in_array($dataType, ["char", "nchar", "varchar", "nvarchar", "varchar(max)", "nvarchar(max)"]));
}

function dataTypeIsUnicode($dataType)
{
    return (in_array($dataType, ["nchar", "nvarchar", "nvarchar(max)"]));
}

function dataTypeIsBinary($dataType)
{
    return (in_array($dataType, ["binary", "varbinary", "varbinary(max)"]));
}

function getPDOType($type)
{
    switch($type) {
        case "bigint":
        case "integer":
        case "smallint":
        case "tinyint":
            return PDO::PARAM_INT;
        case "bit":
            return PDO::PARAM_BOOL;
        case "real":
        case "float":
        case "double":
        case "numeric":
        case "time":
        case "date":
        case "datetime2":
        case "datetime":
        case "datetimeoffset":
        case "smalldatetime":
        case "money":
        case "smallmoney";
        case "xml":
        case "uniqueidentifier":
        case "char":
        case "varchar":
        case "varchar(max)":
        case "nchar":
        case "nvarchar":
        case "nvarchar(max)":
            return PDO::PARAM_STR;
        case "binary":
        case "varbinary":
        case "varbinary(max)":
            return PDO::PARAM_LOB;
        default:
            die("Case is missing for $type type in getPDOType.\n");
    }
}

?>
