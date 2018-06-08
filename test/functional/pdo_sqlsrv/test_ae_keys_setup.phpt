--TEST--
Test the existence of Windows Always Encrypted keys generated in the database setup
--DESCRIPTION--
This test iterates through the rows of sys.column_master_keys and/or 
sys.column_encryption_keys to look for the specific column master key and 
column encryption key generated in the database setup
--SKIPIF--
<?php require('skipif_unix.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
$conn = connect();
    
if (isAEQualified($conn)){
    $query = "SELECT name FROM sys.column_master_keys";
    $stmt = $conn->query($query);

    // Do not assume the master key must be the first one created
    $found = false;
    while ($master_key_row = $stmt->fetch()) {
        if ($master_key_row[0] == 'AEMasterKey') {
            $found = true;
        }
    }
    if (!$found) {
        die("Windows Column Master Key not created.\n");
    }
    
    // Do not assume the encryption key must be the first one created
    $query = "SELECT name FROM sys.column_encryption_keys";
    $stmt = $conn->query($query);
    
    $found = false;
    while ($encryption_key_row = $stmt->fetch()) {
        if ($encryption_key_row[0] == 'AEColumnKey') {
            $found = true;
        }
    }
    if (!$found) {
        die("Windows Column Encryption Key not created.\n");
    }
    unset($stmt);
}

echo "Test Successfully done.\n";
unset($conn);
?>
--EXPECT--
Test Successfully done.