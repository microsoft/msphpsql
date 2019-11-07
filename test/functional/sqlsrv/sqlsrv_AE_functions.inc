<?php

// Connect and clear the procedure cache
function connect($server, $attestation_info)
{
    include("MsSetup.inc");
    $options = array('database'=>$database,
                     'uid'=>$userName,
                     'pwd'=>$userPassword,
                     'CharacterSet'=>'UTF-8',
                     'ColumnEncryption'=>$attestation_info,
                     );

    if ($keystore == 'akv') {
        if ($AKVKeyStoreAuthentication == 'KeyVaultPassword') {
            $security_info = array('KeyStoreAuthentication'=>$AKVKeyStoreAuthentication,
                                   'KeyStorePrincipalId'=>$AKVPrincipalName,
                                   'KeyStoreSecret'=>$AKVPassword,
                                   );
        } elseif ($AKVKeyStoreAuthentication == 'KeyVaultClientSecret') {
            $security_info = array('KeyStoreAuthentication'=>$AKVKeyStoreAuthentication,
                                   'KeyStorePrincipalId'=>$AKVClientID,
                                   'KeyStoreSecret'=>$AKVSecret,
                                   );
        } else {
            die("Incorrect value for KeyStoreAuthentication keyword!\n");
        }

        $options = array_merge($options, $security_info);
    }

    $conn = sqlsrv_connect($server, $options);
    if (!$conn) {
        echo "Connection failed\n";
        print_r(sqlsrv_errors());
    }

    // Check that enclave computations are enabled
    // See https://docs.microsoft.com/en-us/sql/relational-databases/security/encryption/configure-always-encrypted-enclaves?view=sqlallproducts-allversions#configure-a-secure-enclave
    $query = "SELECT [name], [value], [value_in_use] FROM sys.configurations WHERE [name] = 'column encryption enclave type';";
    $stmt = sqlsrv_query($conn, $query);
    $info = sqlsrv_fetch_array($stmt);
    if ($info['value'] != 1 or $info['value_in_use'] != 1) {
        die("Error: enclave computations are not enabled on the server!");
    }

    // Enable rich computations
    sqlsrv_query($conn, "DBCC traceon(127,-1);");

    // Free the encryption cache to avoid spurious 'operand type clash' errors
    sqlsrv_query($conn, "DBCC FREEPROCCACHE");

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

function insertValues($conn, $insertQuery, $dataTypes, $testValues)
{
    for ($v = 0; $v < sizeof($testValues['bigint']); ++$v) {
        $insertValues = array();

        // two copies of each value for the two columns for each data type
        foreach ($dataTypes as $type) {
            $insertValues[] = $testValues[$type][$v];
            $insertValues[] = $testValues[$type][$v];
        }

        // Insert the data using sqlsrv_prepare()
        $stmt = sqlsrv_prepare($conn, $insertQuery, $insertValues);
        if ($stmt == false) {
            print_r(sqlsrv_errors());
            die("Inserting values in encrypted table failed at prepare\n");
        }

        if (sqlsrv_execute($stmt) == false) {
            print_r(sqlsrv_errors());
            die("Inserting values in encrypted table failed at execute\n");
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
    if (!sqlsrv_execute($nonAEstmt)) {
        print_r(sqlsrv_errors());
        die("Executing non-AE statement failed!\n");
    }

    if(!sqlsrv_execute($AEstmt)) {
        if ($attestation == 'enabled') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r(sqlsrv_errors());
                    die("Equality comparison failed for deterministic encryption!\n");
                } else {
                    $e = sqlsrv_errors();
                    checkErrors($e, array('42000', '33277'));
                }
            } elseif (isEnclaveEnabled($key)) {
                $e = sqlsrv_errors();
                checkErrors($e, array('42000', '33546'));
            } elseif (!isEnclaveEnabled($key)) {
                $e = sqlsrv_errors();
                checkErrors($e, array('42000', '33277'));
            }
        } elseif ($attestation == 'wrongurl') {
            if ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r(sqlsrv_errors());
                    die("Equality comparison failed for deterministic encryption!\n");
                } else {
                    $e = sqlsrv_errors();
                    checkErrors($e, array('42000', '33277'));
                }
            } elseif (isEnclaveEnabled($key)) {
                $e = sqlsrv_errors();
                checkErrors($e, array('CE405', '0'));
            } elseif (!isEnclaveEnabled($key)) {
                $e = sqlsrv_errors();
                checkErrors($e, array('42000', '33277'));
            }
        } elseif ($attestation == 'correct') {
            if (!isEnclaveEnabled($key) and $encryptionType == 'Randomized') {
                $e = sqlsrv_errors();
                checkErrors($e, array('42000', '33277'));
            } elseif ($encryptionType == 'Deterministic') {
                if ($comparison == '=') {
                    print_r(sqlsrv_errors());
                    die("Equality comparison failed for deterministic encryption!\n");
                } else {
                    $e = sqlsrv_errors();
                    checkErrors($e, array('42000', '33277'));
                }
            } else {
                print_r(sqlsrv_errors());
                die("Comparison failed for correct attestation when it shouldn't have!\n");
            }
        } else {
            print_r(sqlsrv_errors());
            die("Unexpected error occurred in compareResults!\n");
        }
    } else {
        // char and nchar may not return the same results - at this point
        // we've verified that statement execution works so just return
        // TODO: Check if this bug is fixed and if so, remove this if block
        if ($type == 'char' or $type == 'nchar') {
            return;
        }

        while($AEres = sqlsrv_fetch_array($AEstmt, SQLSRV_FETCH_NUMERIC)) {
            $nonAEres = sqlsrv_fetch_array($nonAEstmt, SQLSRV_FETCH_NUMERIC);
            if (!$nonAEres) {
                print_r($AEres);
                print_r(sqlsrv_errors());
                print_r("Too many AE results for operation $comparison and data type $type!\n");
            } else {
                $i = 0;
                foreach ($AEres as $AEr) {
                    if ($AEr != $nonAEres[$i]) {
                        print_r("AE and non-AE results are different for operation $comparison and data type $type! For field $i, got AE result ".$AEres[$i]." and non-AE result ".$nonAEres[$i]."\n");
                        print_r(sqlsrv_errors());
                    }
                    ++$i;
                }
            }
        }

        if ($rr = sqlsrv_fetch_array($nonAEstmt)) {
            print_r($rr);
            print_r(sqlsrv_errors());
            print_r("Too many non-AE results for operation $comparison and data type $type!\n");
        }
    }
}

// testCompare selects based on a comparison in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
// Arguments:
// resource $conn: The connection
// string   $tableName: Thable name
// array    $comparisons: Comparison operations from AE_v2_values.inc
// array    $dataTypes: Data types from AE_v2_values.inc
// array    $colNames: Column names
// array    $thresholds: Values to use comparison operators against, from AE_v2_values.inc
// string   $key: Name of the encryption key
// integer  $length: Length of the string types, from AE_v2_values.inc
// string   $encryptionType: Type of encryption, randomized or deterministic
// string   $attestation: Type of attestation - 'correct', 'enabled', or 'wrongurl'
function testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, $attestation)
{
    foreach ($comparisons as $comparison) {
        foreach ($dataTypes as $type) {

            // Unicode operations with AE require the PHPTYPE to be specified to
            // UTF-8 and the Latin1_General_BIN2 collation. If the COLLATE
            // clause is left out, we get different results between the
            // encrypted and non-encrypted columns (probably because the
            // collation was only changed in the encryption query).
            $string = dataTypeIsStringMax($type);
            $unicode = dataTypeIsUnicode($type);
            $collate = $string ? " COLLATE Latin1_General_BIN2" : "";
            $phptype = $unicode ? SQLSRV_PHPTYPE_STRING('UTF-8') : null;

            $param = array(array($thresholds[$type], SQLSRV_PARAM_IN, $phptype, getSQLType($type, $length)));
            $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE ".$comparison." ?".$collate;
            $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." ".$comparison." ?".$collate;

            $AEstmt = sqlsrv_prepare($conn, $AEQuery, $param);
            if (!$AEstmt) {
                print_r(sqlsrv_errors());
                die("Preparing AE statement for comparison failed! Comparison $comparison, type $type\n");
            }

            $nonAEstmt = sqlsrv_prepare($conn, $nonAEQuery, $param);
            if (!$nonAEstmt) {
                print_r(sqlsrv_errors());
                die("Preparing non-AE statement for comparison failed! Comparison $comparison, type $type\n");
            }

            compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $comparison, $type);
        }
    }
}

// testPatternMatch selects based on a pattern in the WHERE clause and compares
// the results between encrypted and non-encrypted columns, checking that the
// results are identical
function testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, $attestation)
{
    // TODO: Pattern matching doesn't work in AE for non-string types
    // without an explicit cast
    foreach ($dataTypes as $type) {
        if (!dataTypeIsStringMax($type)) {
            continue;
        }

        foreach ($patterns[$type] as $pattern) {
            $patternarray = array($pattern,
                                  $pattern."%",
                                  "%".$pattern,
                                  "%".$pattern."%",
                                  );

            foreach ($patternarray as $spattern) {

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
                $slength = $unicode ? iconv_strlen($spattern) : strlen($spattern);
                $collate = $unicode ? " COLLATE Latin1_General_BIN2" : "";
                $phptype = $unicode ? SQLSRV_PHPTYPE_STRING('UTF-8') : null;
                $sqltype = $unicode ? SQLSRV_SQLTYPE_NCHAR($slength) : SQLSRV_SQLTYPE_CHAR($slength);

                $param = array(array($spattern, SQLSRV_PARAM_IN, $phptype, $sqltype));
                $AEQuery = "SELECT ".$colNames[$type]."_AE FROM $tableName WHERE ".$colNames[$type]."_AE LIKE ?".$collate;
                $nonAEQuery = "SELECT ".$colNames[$type]." FROM $tableName WHERE ".$colNames[$type]." LIKE ?".$collate;

                $AEstmt = sqlsrv_prepare($conn, $AEQuery, $param);
                if (!$AEstmt) {
                    print_r(sqlsrv_errors());
                    die("Preparing AE statement for comparison failed! Comparison $comparison, type $type\n");
                }

                $nonAEstmt = sqlsrv_prepare($conn, $nonAEQuery, $param);
                if (!$nonAEstmt) {
                    print_r(sqlsrv_errors());
                    die("Preparing non-AE statement for comparison failed! Comparison $comparison, type $type\n");
                }

                compareResults($AEstmt, $nonAEstmt, $key, $encryptionType, $attestation, $pattern, $type);
            }
        }
    }
}

// Check that the expected errors ($codes) is found in the output of sqlsrv_errors() ($errors)
function checkErrors($errors, ...$codes)
{
    $codeFound = false;

    foreach ($codes as $code) {
        if ($code[0]==$errors[0][0] and $code[1]==$errors[0][1]) {
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

function getSQLType($type, $length)
{
    switch($type)
    {
        case "bigint":
            return SQLSRV_SQLTYPE_BIGINT;
        case "integer":
            return SQLSRV_SQLTYPE_INT;
        case "smallint":
            return SQLSRV_SQLTYPE_SMALLINT;
        case "tinyint":
            return SQLSRV_SQLTYPE_TINYINT;
        case "bit":
            return SQLSRV_SQLTYPE_BIT;
        case "real":
            return SQLSRV_SQLTYPE_REAL;
        case "float":
        case "double":
            return SQLSRV_SQLTYPE_FLOAT;
        case "numeric":
            return SQLSRV_SQLTYPE_NUMERIC(18,0);
        case "time":
            return SQLSRV_SQLTYPE_TIME;
        case "date":
            return SQLSRV_SQLTYPE_DATE;
        case "datetime":
            return SQLSRV_SQLTYPE_DATETIME;
        case "datetime2":
            return SQLSRV_SQLTYPE_DATETIME2;
        case "datetimeoffset":
            return SQLSRV_SQLTYPE_DATETIMEOFFSET;
        case "smalldatetime":
            return SQLSRV_SQLTYPE_SMALLDATETIME;
        case "money":
            return SQLSRV_SQLTYPE_MONEY;
        case "smallmoney":
            return SQLSRV_SQLTYPE_SMALLMONEY;
        case "xml":
            return SQLSRV_SQLTYPE_XML;
        case "uniqueidentifier":
            return SQLSRV_SQLTYPE_UNIQUEIDENTIFIER;
        case "char":
            return SQLSRV_SQLTYPE_CHAR($length);
        case "varchar":
            return SQLSRV_SQLTYPE_VARCHAR($length);
        case "varchar(max)":
            return SQLSRV_SQLTYPE_VARCHAR('max');
        case "nchar":
            return SQLSRV_SQLTYPE_NCHAR($length);
        case "nvarchar":
            return SQLSRV_SQLTYPE_NVARCHAR($length);
        case "nvarchar(max)":
            return SQLSRV_SQLTYPE_NVARCHAR('max');
        case "binary":
        case "varbinary":
        case "varbinary(max)":
            // Using a binary type here produces a 'Restricted data type attribute violation'
            return SQLSRV_SQLTYPE_BIGINT;
        default:
            die("Case is missing for $type type in getSQLType.\n");
    }
}

?>
