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
        } elseif ($AKVKeyStoreAuthentication == 'KeyVaultClientSecret') {
            $security_info .= ";KeyStoreAuthentication=$AKVKeyStoreAuthentication";
            $security_info .= ";KeyStorePrincipalId=$AKVClientID";
            $security_info .= ";KeyStoreSecret=$AKVSecret";
        } else {
            die("Incorrect value for KeyStoreAuthentication keyword!\n");
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
// be preceded by ALTER TABLE. This query can be used to both encrypt plaintext
// columns and to re-encrypt encrypted columns.
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

function insertValues($conn, $insertQuery, $dataTypes, $testValues)
{
    for ($v = 0; $v < sizeof($testValues['bigint']); ++$v) {
        $insertValues = array();

        foreach ($dataTypes as $type) {
            $insertValues[] = $testValues[$type][$v];
            $insertValues[] = $testValues[$type][$v];
        }

        // Insert the data using PDO::prepare()
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
// Arguments:
// statement $AEstmt: Prepared statement fetching encrypted data
// statement $nonAEstmt: Prepared statement fetching non-encrypted data
// string   $key: Name of the encryption key
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', or 'wrongurl'
// string   $comparison: Comparison operator
// string   $type: Data type the comparison is operating on
function compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $comparison='', $type='')
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
            } elseif (isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33546'));
            } elseif (!isEnclaveEnabled($key)) {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } else {
                print_r($error);
                die("AE statement execution failed when it shouldn't!");
            }
        } elseif ($attestation == 'wrongurl') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    $e = $error->errorInfo;
                    die("Equality comparison failed for deterministic encryption!\n");
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
                die("AE statement execution failed when it shouldn't!");
            }
        } elseif ($attestation == 'correct') {
            if (!isEnclaveEnabled($key) and $encryptionType == 'Randomized') {
                $e = $error->errorInfo;
                checkErrors($e, array('42000', '33277'));
            } elseif ($encryptionType == 'Deterministic') {
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
        } else {
            print_r($error);
            die("Unexpected error occurred in compareResults!\n");
        }

        return;
    }

    $AEres = $AEstmt->fetchAll(PDO::FETCH_NUM);
    $nonAEres = $nonAEstmt->fetchAll(PDO::FETCH_NUM);
    $AEcount = count($AEres);
    $nonAEcount = count($nonAEres);

    if ($type == 'char' or $type == 'nchar') {
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
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', or 'wrongurl'
function testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $key, $encryptionType, $attestation)
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

            compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $comparison, $type);
        }
    }
}

// testPatternMatch selects based on a pattern in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
// Arguments:
// resource $conn: The connection
// string   $tableName: Table name
// array    $patterns: Patterns to match against, from AE_v2_values.inc
// array    $dataTypes: Data types from AE_v2_values.inc
// array    $colNames: Column names
// string   $key: Name of the encryption key
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', or 'wrongurl'
function testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $attestation)
{
    foreach ($dataTypes as $type) {

        // TODO: Pattern matching doesn't work in AE for non-string types
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

                // Unicode operations with AE require the Latin1_General_BIN2
                // collation. If the COLLATE clause is left out, we get different
                // results between the encrypted and non-encrypted columns (probably
                // because the collation was only changed in the encryption query).
                $unicode = dataTypeIsUnicode($type);
                $collate = $unicode ? " COLLATE Latin1_General_BIN2" : "";
                $PDOType = getPDOType($type);

                $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE LIKE ?".$collate;
                $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." LIKE ?".$collate;

                try {
                    $AEstmt = $conn->prepare($AEQuery);
                    $AEstmt->bindParam(1, $spattern, $PDOType);
                    $nonAEstmt = $conn->prepare($nonAEQuery);
                    $nonAEstmt->bindParam(1, $spattern, $PDOType);
                } catch (PDOException $error) {
                    print_r($error);
                    die("Preparing/binding statements for comparison failed");
                }

                compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $pattern, $type);
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
    return (in_array($dataType, ["binary", "varbinary", "char", "nchar", "varchar", "nvarchar", "varchar(max)", "nvarchar(max)"]));
}

function dataTypeNeedsCollate($dataType)
{
    return (in_array($dataType, ["char", "nchar", "varchar", "nvarchar", "varchar(max)", "nvarchar(max)"]));
}

function dataTypeIsUnicode($dataType)
{
    return (in_array($dataType, ["nchar", "nvarchar", "nvarchar(max)"]));
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
