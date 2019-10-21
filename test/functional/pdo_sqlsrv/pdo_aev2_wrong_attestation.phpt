--TEST--
Test rich computations and in place encryption with AE v2.
--DESCRIPTION--
This test does the following:
1. Create an encrypted table with two columns for each AE-supported data type, one encrypted and one not encrypted.
2. Insert some data.
3. Disconnect and reconnect with a faulty attestation URL.
4. Test comparison and pattern matching. Equality should work with deterministic encryption as in AE v1, but other computations should fail.
5. Try re-encrypting the table. This should fail.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
include("MsSetup.inc");
include("AE_v2_values.inc");
include("pdo_AE_functions.inc");

$initialAttestation = $attestation;

// Create a table for each key and encryption type, re-encrypt using each
// combination of target key and target encryption
foreach ($keys as $key) {
    foreach ($encryptionTypes as $encryptionType) {

        // $count is used to ensure we only run TestCompare and 
        // TestPatternMatch once for the initial table
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
                
                // Reconnect with a faulty attestation URL
                $comma = strpos($attestation, ',');
                $newAttestation = substr_replace($attestation, 'x', $comma+1, 0);

                $conn = connect($server, $newAttestation);

                if ($count == 0) TestCompare($conn, $tableName, $comparisons, $dataTypes, $colNames, $thresholds, $length, $key, $encryptionType, 'wrongurl');
                if ($count == 0) TestPatternMatch($conn, $tableName, $patterns, $dataTypes, $colNames, $length, $key, $encryptionType, 'wrongurl');
                ++$count;
                
                if ($key == $targetKey and $encryptionType == $targetType)
                    continue;

                $alterQuery = constructAlterQuery($tableName, $colNamesAE, $dataTypes, $targetKey, $targetType, $slength);
                
                try {
                    $stmt = $conn->query($alterQuery);

                    // Query should fail and trigger catch block before getting here
                    die("Encrypting should have failed with key $targetKey and encryption type $targetType\n");
                } catch(Exception $error) {
                    if (!isEnclaveEnabled($key) or !isEnclaveEnabled($targetKey)) {
                        $e = $error->errorInfo;
                        checkErrors($e, array('42000', '33543'));
                    } else {
                        $e = $error->errorInfo;
                        checkErrors($e, array('CE405', '0'));
                    }
                }
            }
        }
    }
}

echo "Done.\n";

?>
