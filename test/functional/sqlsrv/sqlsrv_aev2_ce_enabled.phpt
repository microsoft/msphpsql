--TEST--
Try re-encrypting a table with ColumnEncryption set to 'enabled', which should fail.
--DESCRIPTION--
This test cycles through $encryptionTypes and $keys, creating an encrypted table
each time, then cycles through $targetTypes and $targetKeys to try re-encrypting
the table with different combinations of enclave-enabled and non-enclave keys
and encryption types.
The sequence of operations is the following:
1. Connect with correct attestation information.
2. Create an encrypted table with two columns for each AE-supported data type, one encrypted and one not encrypted.
3. Insert some data.
4. Disconnect and reconnect with ColumnEncryption set to 'enabled'.
5. Test comparison and pattern matching by comparing the results for the encrypted and non-encrypted columns.
   Equality should work with deterministic encryption as in AE v1, but other computations should fail.
6. Try re-encrypting the table. This should fail.
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
        foreach ($targetKeys as $targetKey) {
            foreach ($targetTypes as $targetType) {

                $conn = connect($server, $initialAttestation);

                // Create an encrypted table
                $createQuery = constructAECreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength, $key, $encryptionType);
                $insertQuery = constructInsertQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS $tableName");
                $stmt = sqlsrv_query($conn, $createQuery);
                if(!$stmt)
                {
                    print_r(editErrors(sqlsrv_errors()));
                    die("Table creation failed!");
                }

                insertValues($conn, $insertQuery, $dataTypes, $testValues);
                unset($conn);

                // Reconnect with ColumnEncryption set to 'enabled'
                $newAttestation = 'enabled';

                $conn = connect($server, $newAttestation);

                if ($count == 0) {
                    testCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, 'enabled');
                    testPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $key, $encryptionType, 'enabled');
                }
                ++$count;

                if ($key == $targetKey and $encryptionType == $targetType) {
                    continue;
                }

                $alterQuery = constructAlterQuery($tableName, $colNamesAE, $dataTypes, $targetKey, $targetType, $slength);

                // Split the data type array, because for some reason we get an error
                // if the query is too long (>2000 characters)
                $splitDataTypes = array_chunk($dataTypes, 5);
                $encryptionFailed = false;

                foreach ($splitDataTypes as $split) {

                    $alterQuery = constructAlterQuery($tableName, $colNamesAE, $split, $targetKey, $targetType, $slength);
                    $stmt = sqlsrv_query($conn, $alterQuery);

                    if(!$stmt) {
                        if (!isEnclaveEnabled($key) or !isEnclaveEnabled($targetKey)) {

                            $e = sqlsrv_errors();
                            checkErrors($e, array('42000', '33543'));
                            $encryptionFailed = true;
                            continue;
                        } else {
                            $e = sqlsrv_errors();
                            checkErrors($e, array('42000', '33546'));
                            $encryptionFailed = true;
                            continue;
                        }

                        continue;
                    } else {
                        die("Encrypting should have failed with key $targetKey and encryption type $encryptionType!\n");
                    }
                }

                if ($encryptionFailed) {
                    continue;
                }
            }
        }
    }
}

echo "Done.\n";

?>
--EXPECT--
Done.
