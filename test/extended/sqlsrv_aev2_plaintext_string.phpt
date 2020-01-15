--TEST--
Test rich computations and in-place encryption of plaintext with AE v2.
--DESCRIPTION--
This test cycles through $ceValues, $encryptionTypes, and $keys, creating a
plaintext table each time, then trying to encrypt it with different combinations
of enclave-enabled and non-enclave keys and encryption types. It then reconnects
and cycles through $targetCeValues, $targetTypes and $targetKeys to try re-encrypting
the table with different target combinations of enclave-enabled and non-enclave keys
and encryption types.
The sequence of operations is the following:
1. Create a table in plaintext with two columns for each AE-supported data type.
2. Insert some data in plaintext.
3. Encrypt one column for each data type.
4. Perform rich computations on each AE-enabled column (comparisons and pattern matching)
   and compare the result to the same query on the corresponding non-AE column for each data type.
5. Ensure the two results are the same.
6. Disconnect and reconnect with a new value for ColumnEncryption.
7. Compare computations as in 4. above.
8. Re-encrypt the table using a new key and/or encryption type.
9. Compare computations as in 4. above.
This test only tests string types, because if we try to tests all types at
once, eventually a CE405 error is returned.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("AE_v2_values.inc");
require_once("sqlsrv_AE_functions.inc");

runPlaintextTest($ceValues, $keys, $encryptionTypes, 
                 $targetCeValues, $targetKeys, $targetTypes, 
                 $tableName, $dataTypes2, $colNames2, $colNamesAE2, 
                 $length, $slength, $testValues,
                 $comparisons, $patterns, $thresholds);

echo "Done.\n";

?>
--EXPECT--
Done.
