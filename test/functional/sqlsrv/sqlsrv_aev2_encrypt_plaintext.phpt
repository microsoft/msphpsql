--TEST--
Test rich computations and in-place encryption of plaintext with AE v2.
--DESCRIPTION--
This test cycles through $encryptionTypes and $keys, creating a plaintext table
each time, then trying to encrypt it with different combinations of enclave-enabled and non-enclave keys
and encryption types. It then cycles through $targetTypes and $targetKeys to try re-encrypting
the table with different target combinations of enclave-enabled and non-enclave keys
and encryption types.
The sequence of operations is the following:
1. Create a table in plaintext with two columns for each AE-supported data type.
2. Insert some data in plaintext.
3. Encrypt one column for each data type.
4. Perform rich computations on each AE-enabled column (comparisons and pattern matching) and compare the result
   to the same query on the corresponding non-AE column for each data type.
5. Ensure the two results are the same.
6. Re-encrypt the table using new key and/or encryption type.
7. Compare computations as in 4. above.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("AE_v2_values.inc");
require_once("sqlsrv_AE_functions.inc");

$initialAttestation = $attestation;

// Create a table for each key and encryption type, re-encrypt using each
// combination of target key and target encryption
foreach ($keys as $key) {
    foreach ($encryptionTypes as $encryptionType) {

        // $count is used to ensure we only run testCompare and
        // testPatternMatch once for the initial table
        $count = 0;
        $conn = connect($server, $attestation);

        foreach ($targetKeys as $targetKey) {
            foreach ($targetTypes as $targetType) {

                // Free the encryption cache to avoid spurious 'operand type clash' errors
                sqlsrv_query($conn, "DBCC FREEPROCCACHE");

                // Create and populate a non-encrypted table
                $createQuery = constructCreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength);
                $insertQuery = constructInsertQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS $tableName");
                $stmt = sqlsrv_query($conn, $createQuery);
                if(!$stmt) {
                    print_r(sqlsrv_errors());
                    die("Creating an encrypted table failed when it shouldn't have!\n");
                }

                insertValues($conn, $insertQuery, $dataTypes, $testValues);

                if ($count == 0) {

                    // Split the data type array, because for some reason we get an error
                    // if the query is too long (>2000 characters)
                    // TODO: This is a known issue, follow up on it.
                    $splitDataTypes = array_chunk($dataTypes, 5);
                    foreach ($splitDataTypes as $split)
                    {
                        $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $key, $encryptionType, $slength);

                        $stmt = sqlsrv_query($conn, $alterQuery);
                        $encryptionFailed = false;

                        if(!$stmt) {
                            if (!isEnclaveEnabled($key)) {
                                $e = sqlsrv_errors();
                                checkErrors($e, array('42000', '33543'));
                                $encryptionFailed = true;
                                continue;
                            } else {
                                print_r(sqlsrv_errors());
                                die("Encrypting failed when it shouldn't have!\n");
                            }
                        } else {
                            if (!isEnclaveEnabled($key)) {
                                die("Encrypting should have failed with key $key and encryption type $encryptionType\n");
                            }
                        }
                    }
                }

                if ($encryptionFailed) continue;

                if ($count == 0) {
                    testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, 'correct');
                    testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, 'correct');
                }
                ++$count;

                if ($key == $targetKey and $encryptionType == $targetType) {
                    continue;
                }

                // Try re-encrypting the table
                $encryptionFailed = false;
                foreach ($splitDataTypes as $split) {
                    $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $targetKey, $targetType, $slength);

                    $stmt = sqlsrv_query($conn, $alterQuery);
                    if(!$stmt) {
                        if (!isEnclaveEnabled($targetKey)) {
                            $e = sqlsrv_errors();
                            checkErrors($e, array('42000', '33543'));
                            $encryptionFailed = true;
                            continue;
                        } else {
                            print_r(sqlsrv_errors());
                            die("Encrypting failed when it shouldn't have!\n");
                        }
                    } else {
                        if (!isEnclaveEnabled($targetKey)) {
                            die("Encrypting should have failed with key $targetKey and encryption type $targetType\n");
                        }
                    }
                }

                if ($encryptionFailed) {
                    continue;
                }

                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $targetKey, $targetType, 'correct');
                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $targetKey, $targetType, 'correct');
            }
        }
    }
}

echo "Done.\n";

?>
--EXPECT--
Done.
