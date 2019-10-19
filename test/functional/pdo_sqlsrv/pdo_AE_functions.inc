<?php

// Connect and clear the procedure cache
function connect($server, $attestation_info)
{
    include("MsSetup.inc");
    $options = "sqlsrv:Server=$server;Database=$databaseName;ColumnEncryption=$attestation_info";
                     
    if ($keystore == 'akv') {
        
        $security_info = '';
        if ($AKVKeyStoreAuthentication == 'KeyVaultPassword') {
            $security_info .= ";KeyStoreAuthentication=$AKVKeyStoreAuthentication";
            $security_info .= ";KeyStorePrincipalId=$AKVPrincipalName";
            $security_info .= ";KeyStoreSecret=$AKVPassword";
        } else if ($AKVKeyStoreAuthentication == 'KeyVaultClientSecret') {
            $security_info .= ";KeyStoreAuthentication=$AKVKeyStoreAuthentication";
            $security_info .= ";KeyStorePrincipalId=$AKVClientID";
            $security_info .= ";KeyStoreSecret=$AKVSecret";
        }
    
        $options .= $security_info;
    }
    
    $conn = new PDO($options, $uid, $pwd);    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    
    // Check that enclave computations are enabled
    // See https://docs.microsoft.com/en-us/sql/relational-databases/security/encryption/configure-always-encrypted-enclaves?view=sqlallproducts-allversions#configure-a-secure-enclave
    $query = "SELECT [name], [value], [value_in_use] FROM sys.configurations WHERE [name] = 'column encryption enclave type';";
    $stmt = $conn->query($query);
    $info = $stmt->fetch();
    if ($info['value'] != 1 or $info['value_in_use'] != 1) {
        die("Error: enclave computations are not enabled on the server!");
    }
    
    // Free the encryption cache to avoid spurious 'operand type clash' errors
    $conn->exec("DBCC FREEPROCCACHE");

    return $conn;
}

// This CREATE TABLE query simply creates a non-encrypted table with 
// two columns for each data type side by side
function createCreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength)
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
    
    $query = substr($query, 0, -7)."\n)";

    return $query;
}

// The ALTER TABLE query encrypts columns. Each ALTER COLUMN directive must
// be preceded by ALTER TABLE
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
function createAECreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength, $key, $encryptionType)
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
    
    $query = substr($query, 0, -6)."\n)";

    return $query;
}

// The INSERT query for the table
function formulateSetupQuery($tableName, &$dataTypes, &$colNames, &$colNamesAE)
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

    $queryTypes = substr($queryTypes, 0, -2).")";
    $valuesString = substr($valuesString, 0, -2).")";

    $insertQuery = "INSERT INTO $tableName ".$queryTypes." ".$valuesString;

    return $insertQuery;
}

function insertValues($conn, $insertQuery, $dataTypes, $testValues)
{
    for ($v = 0; $v < sizeof($testValues['bigint']); ++$v) {
        $insertValues = array();
        
        foreach ($dataTypes as $type) {
            $insertValues[] = $testValues[$type][$v];
            $insertValues[] = $testValues[$type][$v];
        }

        // Insert the data using sqlsrv_prepare()
        try {
            $stmt = $conn->prepare($insertQuery);
            $stmt->execute($insertValues);
        } catch (PDOException $error) {
            print_r($error);
            die("Inserting values in encrypted table failed\n");
        }
    }
}

// compareResults checks that the results between the encrypted and non-encrypted
// columns are identical if statement execution succeeds. If statement execution
// fails, this function checks for the correct error.
function compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $comparison='')
{
    try {
        $nonAEstmt->execute();
    } catch(Exception $error) {
        print_r($error);
        die("Executing non-AE statement failed!\n");
    }
    
    try {
        $AEstmt->execute();
    } catch(Exception $error) {
        if ($attestation == 'enabled') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r($error);
                    die("Equality comparison failed for deterministic encryption!\n");
                } else {
                    $e = $error->errorInfo;
                    checkErrors($e, array('42000', '33277'));
                }
            } else if (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33546'));
            } else if (!isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } else {
                print_r($error);
                die("AE statement execution when it shouldn't!");
            }
        } else if ($attestation == 'wrongurl') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    $e = $error->errorInfo;
                    die("Equality comparison failed for deterministic encryption!\n");
                } else {
                    $e = $error->errorInfo;
                    checkErrors($e, array('42000', '33277'));
                }
            } else if (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('CE405', '0'));
            } else if (!isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } else {
                print_r($error);
                die("AE statement execution when it shouldn't!");
            }
        } else if ($attestation == 'correct') {
            if (!isEnclaveEnabled($key) and $encryptionType == 'Randomized') {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } else if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r($error);
                    die("Equality comparison failed for deterministic encryption!\n");
                } else {
                    $e = $error->errorInfo;
                    checkErrors($e, array('42000', '33277'));
                }
            } else {
                print_r($error);
                die("Comparison failed for correct attestation when it shouldn't have!\n");
            }
        }
        
        return;
    }

    while($AEres = $AEstmt->fetch(PDO::FETCH_NUM)) {
        $nonAEres = $nonAEstmt->fetch(PDO::FETCH_NUM);
        
        if (!$nonAEres) {
            print_r($nonAEstmt->errorInfo);
            die("Too many AE results!\n");
        } else {
            $i = 0;
            foreach ($AEres as $AEr) {
                if ($AEr != $nonAEres[$i]) {
                    print_r("AE and non-AE results are different! For field $i, got AE result ".$AEres[$i]." and non-AE result ".$nonAEres[$i]."\n");
                    print_r($AEstmt->errorInfo);
                    print_r($nonAEstmt->errorInfo);
                }
                ++$i;
            }
        }
    }

    if ($nonAEstmt->fetch()) {
        print_r($nonAEstmt->errorInfo);
        die("Too many non-AE results!\n");
    }
}

// testCompare selects based on a comparison in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
function TestCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $attestation)
{
    foreach ($comparisons as $comparison) {
        foreach ($dataTypes as $type) {
            
            // Unicode data types require the PHPTYPE to be specified to UTF-8 string
            // and the correct collation. Otherwise we get different results between
            // the encrypted and non-encrypted columns (probably because the collation 
            // was only changed in the encryption query).
            $string = dataTypeIsStringMax($type);
            $collate = $string ? " COLLATE Latin1_General_BIN2" : "";
            $unicode = dataTypeIsUnicode($type);
            $PDOType = GetPDOType($type);
            
            $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE ".$comparison." ?".$collate;
            $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." ".$comparison." ?".$collate;

            try {
                $AEstmt = $conn->prepare($AEQuery);
                $AEstmt->bindParam(1, $thresholds[$type], $PDOType);
                $nonAEstmt = $conn->prepare($nonAEQuery);
                $nonAEstmt->bindParam(1, $thresholds[$type], $PDOType);
            } catch (PDOException $error) {
                print_r($error);
                die("Preparing/binding statements for comparison failed");
            }

            compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $comparison);
        }
    }
}

// testPatternMatch selects based on a pattern in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
function TestPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $length, $key, $encryptionType, $attestation)
{
    foreach ($dataTypes as $type) {
        
        // Pattern matching doesn't work in AE for non-string types
        // without an explicit cast
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
                
                $unicode = dataTypeIsUnicode($type);
                $collate = $unicode ? " COLLATE Latin1_General_BIN2" : "";
                $PDOType = GetPDOType($type);

                $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE LIKE ?".$collate;
                $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." LIKE ?".$collate;

                try {
                    $AEstmt = $conn->prepare($AEQuery);
                    $AEstmt->bindParam(1, $thresholds[$type], $PDOType);
                    $nonAEstmt = $conn->prepare($nonAEQuery);
                    $nonAEstmt->bindParam(1, $thresholds[$type], $PDOType);
                } catch (PDOException $error) {
                    print_r($error);
                    die("Preparing/binding statements for comparison failed");
                }

                compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation);
            }
        }
    }
}

function checkErrors($errors, ...$codes)
{
    $codeFound = false;

    foreach ($codes as $code) {
        if ($code[0]==$errors[0] and $code[1]==$errors[1]) {
            $codeFound = true;
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
    if (strpos($key, '-enclave') !== false) return true;
    else return false;
}

function dataTypeIsString($dataType)
{
    if (in_array($dataType, ["binary", "varbinary", "char", "nchar", "varchar", "nvarchar"]))
        return true;
    else
        return false;
}

function dataTypeIsStringMax($dataType)
{
    if (in_array($dataType, ["binary", "varbinary", "char", "nchar", "varchar", "nvarchar", "varchar(max)", "nvarchar(max)"]))
        return true;
    else
        return false;
}

function dataTypeNeedsCollate($dataType)
{
    if (in_array($dataType, ["char", "nchar", "varchar", "nvarchar", "varchar(max)", "nvarchar(max)"]))
        return true;
    else
        return false;
}

function dataTypeIsUnicode($dataType)
{
    if (in_array($dataType, ["nchar", "nvarchar", "nvarchar(max)"]))
        return true;
    else
        return false;
}

function GetPDOType($type)
{
    switch($type) {
        case "bigint":
        case "integer":
        case "smallint":
        case "tinyint":
            return PDO::PARAM_INT;
            break;
        case "bit":
            return PDO::PARAM_BOOL;
            break;
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
            break;
        case "binary":
        case "varbinary":
        case "varbinary(max)":
            return PDO::PARAM_LOB;
            break;
        default:
            die("Case is missing for $type type in GetPDOType.\n");
    }
}

function GetSQLType($type, $length)
{
    switch($type) {
        case "bigint":
            return SQLSRV_SQLTYPE_BIGINT;
            break;
        case "integer":
            return SQLSRV_SQLTYPE_INT;
            break;
        case "smallint":
            return SQLSRV_SQLTYPE_SMALLINT;
            break;
        case "tinyint":
            return SQLSRV_SQLTYPE_TINYINT;
            break;
        case "bit":
            return SQLSRV_SQLTYPE_BIT;
            break;
        case "real":
            return SQLSRV_SQLTYPE_REAL;
            break;
        case "float":
        case "double":
            return SQLSRV_SQLTYPE_FLOAT;
            break;
        case "numeric":
            return SQLSRV_SQLTYPE_NUMERIC(18,0);
            break;
        case "time":
            return SQLSRV_SQLTYPE_TIME;
            break;
        case "date":
            return SQLSRV_SQLTYPE_DATE;
            break;
        case "datetime":
            return SQLSRV_SQLTYPE_DATETIME;
            break;
        case "datetime2":
            return SQLSRV_SQLTYPE_DATETIME2;
            break;
        case "datetimeoffset":
            return SQLSRV_SQLTYPE_DATETIMEOFFSET;
            break;
        case "smalldatetime":
            return SQLSRV_SQLTYPE_SMALLDATETIME;
            break;
        case "money":
            return SQLSRV_SQLTYPE_MONEY;
            break;
        case "smallmoney":
            return SQLSRV_SQLTYPE_SMALLMONEY;
            break;
        case "xml":
            return SQLSRV_SQLTYPE_XML;
            break;
        case "uniqueidentifier":
            return SQLSRV_SQLTYPE_UNIQUEIDENTIFIER;
            break;
        case "char":
            return SQLSRV_SQLTYPE_CHAR($length);
            break;
        case "varchar":
            return SQLSRV_SQLTYPE_VARCHAR($length);
            break;
        case "varchar(max)":
            return SQLSRV_SQLTYPE_VARCHAR('max');
            break;
        case "nchar":
            return SQLSRV_SQLTYPE_NCHAR($length);
            break;
        case "nvarchar":
            return SQLSRV_SQLTYPE_NVARCHAR($length);
            break;
        case "nvarchar(max)":
            return SQLSRV_SQLTYPE_NVARCHAR('max');
            break;
        case "binary":
        case "varbinary":
        case "varbinary(max)":
            // Using a binary type here produces a 'Restricted data type attribute violation'
            return SQLSRV_SQLTYPE_BIGINT;
            break;
        default:
            die("Case is missing for $type type in GetSQLType.\n");
    }
}

?>