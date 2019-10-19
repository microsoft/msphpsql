--TEST--
Test rich computations and in place encryption with AE v2.
--DESCRIPTION--
This test does the following:
1. Connect with correct attestation information.
1. Create an encrypted table with two columns for each AE-supported data type.
2. Insert some data.
3. Disconnect and reconnect with ColumnEncryption set to 'enabled'.
4. Perform rich computations on each AE-enabled column (comparisons and pattern matching)
   and verify any error messages that crop up.
5. Ensure the two results are the same.
6. Re-encrypt the table using new key and/or encryption type.
7. Compare computations as in 4. above.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
include("MsSetup.inc");
include("AE_v2_values.inc");
include("pdo_AE_functions.inc");

$initialAttestation = $attestation;

foreach ($keys as $key) {
    foreach ($encryptionTypes as $encryptionType) {

        $count = 0;
        foreach ($targetKeys as $targetKey) {
            foreach ($targetTypes as $targetType) {
                
                $conn = connect($server, $initialAttestation);

                // Create an encrypted table
                $createQuery = createAECreateQuery($tableName, $dataTypes, $colNames, $colNamesAE, $slength, $key, $encryptionType);
                $insertQuery = formulateSetupQuery($tableName, $dataTypes, $colNames, $colNamesAE);

                try {
                    $stmt = $conn->query("DROP TABLE IF EXISTS $tableName");
                    $stmt = $conn->query($createQuery);
                } catch(Exception $error) {
                    print_r($error);
                    die("Creating an encrypted table failed when it shouldn't have!\n");
                }

                insertValues($conn, $insertQuery, $dataTypes, $testValues);
                unset($conn);
                
                // Reconnect with ColumnEncryption set to 'enabled'
                $newAttestation = 'enabled';
                $conn = connect($server, $newAttestation);

                if ($count == 0) TestCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, 'enabled');
                if ($count == 0) TestPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $length, $key, $encryptionType, 'enabled');
                ++$count;
                
                if ($key == $targetKey and $encryptionType == $targetType)
                    continue;

                $alterQuery = constructAlterQuery($tableName, $colNamesAE, $dataTypes, $targetKey, $targetType, $slength);
                
                try {
                    $stmt = $conn->query($alterQuery);
                    
                    // Query should fail and trigger catch block before getting here
                    die("Encrypting should have failed with key $targetKey and encryption type $targetType\n");
                } catch (PDOException $error) {
                    if (!isEnclaveEnabled($key) or !isEnclaveEnabled($targetKey)) {
                        $e = $error->errorInfo;
                        checkErrors($e, array('42000', '33543'));
                    } else {
                        $e = $error->errorInfo;
                        checkErrors($e, array('42000', '33546'));
                    }
                }
            }
        }
    }
}

echo "Done.\n";

?>
