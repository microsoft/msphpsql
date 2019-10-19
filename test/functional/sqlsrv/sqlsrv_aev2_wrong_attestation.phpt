--TEST--
Test rich computations and in place encryption with AE v2.
--DESCRIPTION--
This test does the following:
1. Create an encrypted table with two columns for each AE-supported data type.
2. Disconnect and reconnect with a faulty attestation URL.
3. Test comparison and pattern matching. Equality should work with deterministic encryption as in AE v1, but other computations should fail.
4. Try re-encrypting the table. This should fail.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
include("MsSetup.inc");
include("AE_v2_values.inc");
include("sqlsrv_AE_functions.inc");

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

                $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS $tableName");
                $stmt = sqlsrv_query($conn, $createQuery);
                if(!$stmt) {
                    print_r(sqlsrv_errors());
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
                $stmt = sqlsrv_query($conn, $alterQuery);
                
                if(!$stmt) {
                    if (!isEnclaveEnabled($key) or !isEnclaveEnabled($targetKey)) {
                        $e = sqlsrv_errors();
                        checkErrors($e, array('42000', '33543'));
                    } else {
                        $e = sqlsrv_errors();
                        checkErrors($e, array('CE405', '0'));
                    }
                } else {
                    die("Encrypting should have failed with key $targetKey and encryption type $targetType\n");
                }
            }
        }
    }
}

echo "Done.\n";

?>
