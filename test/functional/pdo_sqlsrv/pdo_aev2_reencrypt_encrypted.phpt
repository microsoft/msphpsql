--TEST--
Test rich computations and in place re-encryption with AE v2.
--DESCRIPTION--
This test cycles through $encryptionTypes and $keys, creating an encrypted table
each time, then cycles through $targetTypes and $targetKeys to try re-encrypting
the table with different combinations of enclave-enabled and non-enclave keys
and encryption types.
The sequence of operations is the following:
1. Create an encrypted table with two columns for each AE-supported data type, one encrypted and one not encrypted.
2. Insert some data.
3. Perform rich computations on each AE-enabled column (comparisons and pattern matching) and compare the result
   to the same query on the corresponding non-AE column for each data type.
4. Ensure the two results are the same.
5. Re-encrypt the table using new key and/or encryption type.
6. Compare computations as in 4. above.
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

                // Create an encrypted table
                $createQuery = constructAECreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength, $key, $encryptionType);
                $insertQuery = constructInsertQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                try {
                    $stmt = $conn->query("DROP TABLE IF EXISTS $tableName");
                    $stmt = $conn->query($createQuery);
                } catch(Exception $error) {
                    print_r($error);
                    die("Creating an encrypted table failed when it shouldn't have!\n");
                }

                insertValues($conn, $insertQuery, $dataTypes, $testValues);

                if ($count == 0) {
                    testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $key, $encryptionType, 'correct');
                    testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, 'correct');
                }
                ++$count;

                if ($key == $targetKey and $encryptionType == $targetType) {
                    continue;
                }

                // Split the data type array, because for some reason we get an error
                // if the query is too long (>2000 characters)
                // TODO: This is a known issue, follow up on it.
                $splitDataTypes = array_chunk($dataTypes, 5);
                $encryptionFailed = false;
                foreach ($splitDataTypes as $split) {
                    $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $targetKey, $targetType, $slength);

                    try {
                        $stmt = $conn->query($alterQuery);
                        if (!isEnclaveEnabled($key) or !isEnclaveEnabled($targetKey)) {
                            die("Encrypting should have failed with key $targetKey and encryption type $encryptionType\n");
                        }
                    } catch (PDOException $error) {
                        if (!isEnclaveEnabled($key) or !isEnclaveEnabled($targetKey)) {
                            $e = $error->errorInfo;
                            checkErrors($e, array('42000', '33543'));
                            $encryptionFailed = true;
                            continue;
                        } else {
                            print_r($error);
                            die("Encrypting failed when it shouldn't have! key = $targetKey and type = $targetType\n");
                        }

                        continue;
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
