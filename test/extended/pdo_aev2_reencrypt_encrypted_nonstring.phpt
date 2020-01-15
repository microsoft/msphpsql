--TEST--
Test rich computations and in place re-encryption with AE v2.
--DESCRIPTION--
This test cycles through $ceValues, $encryptionTypes, and $keys, creating
an encrypted table each time, then cycles through $targetCeValues, $targetTypes,
and $targetKeys to try re-encrypting the table with different combinations of
enclave-enabled and non-enclave keys and encryption types.
The sequence of operations is the following:
1. Create an encrypted table with two columns for each AE-supported data type,
   one encrypted and one not encrypted.
2. Insert some data.
3. Perform rich computations on each AE-enabled column (comparisons and pattern matching)
   and compare the result to the same query on the corresponding non-AE column for each data type.
4. Ensure the two results are the same.
5. Disconnect and reconnect with a new value for ColumnEncryption.
6. Compare computations as in 3. above.
7. Re-encrypt the table using a new key and/or encryption type.
8. Compare computations as in 3. above.
This test only tests nonstring types, because if we try to tests all types at
once, eventually a CE405 error is returned.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("AE_v2_values.inc");
require_once("pdo_AE_functions.inc");

runEncryptedTest($ceValues, $keys, $encryptionTypes, 
                 $targetCeValues, $targetKeys, $targetTypes, 
                 $tableName, $dataTypes1, $colNames1, $colNamesAE1, 
                 $length, $slength, $testValues,
                 $comparisons, $patterns, $thresholds);

echo "Done.\n";

?>
--EXPECT--
Done.
