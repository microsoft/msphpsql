--TEST--
Test rich computations and in-place encryption of plaintext with AE v2.
--DESCRIPTION--
This test does the following:
1. Create a table in plaintext with two columns for each AE-supported data type.
2. Insert some data in plaintext.
3. Encrypt one column for each data type.
4. Perform rich computations on each AE-enabled column (comparisons and pattern matching) and compare the result to the same query on the corresponding non-AE column for each data type.
5. Ensure the two results are the same.
6. Re-encrypt the table using new key and/or encryption type.
7. Compare computations as in 4. above.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("AE_v2_values.inc");
require_once("pdo_AE_functions.inc");

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
                $conn->query("DBCC FREEPROCCACHE");

                // Create and populate a non-encrypted table
                $createQuery = constructCreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength);
                $insertQuery = constructInsertQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                try {
                    $stmt = $conn->query("DROP TABLE IF EXISTS $tableName");
                    $stmt = $conn->query($createQuery);
                } catch(Exception $error) {
                    print_r($error);
                    die("Creating table failed when it shouldn't have!\n");
                }

                insertValues($conn, $insertQuery, $dataTypes, $testValues);

                if ($count == 0) {
                    // Split the data type array, because for some reason we get an error
                    // if the query is too long (>2000 characters)
                    // TODO: This is a known issue, follow up on it.
                    $splitDataTypes = array_chunk($dataTypes, 5);
                    foreach ($splitDataTypes as $split) {
                        $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $key, $encryptionType, $slength);
                        $encryptionFailed = false;

                        try {
                            $stmt = $conn->query($alterQuery);
                            if (!isEnclaveEnabled($key)) {
                                die("Encrypting should have failed with key $key and encryption type $encryptionType\n");
                            }
                        } catch (PDOException $error) {
                            if (!isEnclaveEnabled($key)) {
                                $e = $error->errorInfo;
                                checkErrors($e, array('42000', '33543'));
                                $encryptionFailed = true;
                                continue;
                            } else {
                                print_r($error);
                                die("Encrypting failed when it shouldn't have!\n");
                            }
                        }
                    }
                }

                if ($encryptionFailed) continue;

                if ($count == 0) {
                    testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $key, $encryptionType, 'correct');
                    testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, 'correct');
                }
                ++$count;

                if ($key == $targetKey and $encryptionType == $targetType) {
                    continue;
                }

                // Try re-encrypting the table
                foreach ($splitDataTypes as $split) {
                    $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $targetKey, $targetType, $slength);
                    $encryptionFailed = false;

                    try {
                        $stmt = $conn->query($alterQuery);
                        if (!isEnclaveEnabled($targetKey)) {
                            die("Encrypting should have failed with key $targetKey and encryption type $targetType\n");
                        }
                    } catch (Exception $error) {
                        if (!isEnclaveEnabled($targetKey)) {
                            $e = $error->errorInfo;
                            checkErrors($e, array('42000', '33543'));
                            $encryptionFailed = true;
                            continue;
                        } else {
                            print_r($error);
                            die("Encrypting failed when it shouldn't have!\n");
                        }
                    }
                }

                if ($encryptionFailed) {
                    continue;
                }

                testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $targetKey, $targetType, 'correct');
                testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $targetKey, $targetType, 'correct');
            }
        }
    }
}

echo "Done.\n";

?>
--EXPECT--
Done.
