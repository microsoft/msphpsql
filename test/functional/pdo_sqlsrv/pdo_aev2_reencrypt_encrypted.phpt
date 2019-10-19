--TEST--
Test rich computations and in place encryption with AE v2.
--DESCRIPTION--
This test does the following:
1. Create an encrypted table with two columns for each AE-supported data type.
2. Insert some data.
3. Perform rich computations on each AE-enabled column (comparisons and pattern matching) and compare the result to the same query on the corresponding non-AE column for each data type.
4. Ensure the two results are the same.
5. Re-encrypt the table using new key and/or encryption type.
6. Compare computations as in 4. above.
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
        $conn = connect($server, $attestation);

        foreach ($targetKeys as $targetKey) {
            foreach ($targetTypes as $targetType) {
                
                $conn->query("DBCC FREEPROCCACHE");

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

                if ($count == 0) TestCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, 'correct');
                if ($count == 0) TestPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $length, $key, $encryptionType, 'correct');
                ++$count;
        
                if ($key == $targetKey and $encryptionType == $targetType)
                    continue;
                
                // Split the data type array, because for some reason we get an error
                // if the query is too long (>2000 characters)
                $splitDataTypes = array_chunk($dataTypes, 5);
                $encryption_failed = false;
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
                            $encryption_failed = true;
                            continue;
                        } else {
                            print_r($error);
                            die("Encrypting failed when it shouldn't have! key = $targetKey and type = $targetType\n");
                        }
                        
                        continue;
                    }
                }
                
                if ($encryption_failed) continue;
                
                TestCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $targetKey, $targetType, 'correct');
                TestPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $length, $targetKey, $targetType, 'correct');
            }
        }
    }
}

//
echo "Done.\n";

?>
