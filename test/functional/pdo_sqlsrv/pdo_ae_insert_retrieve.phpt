--TEST--
Test for inserting encrypted data and retrieving both encrypted and decrypted data
--DESCRIPTION--
Retrieving SQL query contains encrypted filter
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
try {
    $conn = connect();
    // Create the table
    $tbname = 'Patients';
    $colMetaArr = array( new ColumnMeta("int", "PatientId", "IDENTITY(1,1)"),
                         new ColumnMeta("char(11)", "SSN"),
                         new ColumnMeta("nvarchar(50)", "FirstName", "NULL"),
                         new ColumnMeta("nvarchar(50)", "LastName", "NULL"),
                         new ColumnMeta("date", "BirthDate", null, "randomized"));
    createTable($conn, $tbname, $colMetaArr);

    // insert a row
    $SSN = "795-73-9838";
    $inputs = array( "SSN" => $SSN, "FirstName" => "Catherine", "LastName" => "Abel", "BirthDate" => "1996-10-19" );
    $stmt = insertRow($conn, $tbname, $inputs);

    echo "Retrieving plaintext data:\n";
    $selectSql = "SELECT SSN, FirstName, LastName, BirthDate FROM $tbname WHERE SSN = ?";
    $stmt = $conn->prepare($selectSql);
    $stmt->bindParam(1, $SSN);
    $stmt->execute();
    $decrypted_row = $stmt->fetch(PDO::FETCH_ASSOC);
    foreach ($decrypted_row as $key => $value) {
        print "$key: $value\n";
    }
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getMessage();
}
// for AE only
echo "\nChecking ciphertext data:\n";
if (isColEncrypted()) {
    try {
        $conn1 = connect('', array(), PDO::ERRMODE_EXCEPTION, true);
        $selectSql = "SELECT SSN, FirstName, LastName, BirthDate FROM $tbname";
        $stmt = $conn1->query($selectSql);
        $encrypted_row = $stmt->fetch(PDO::FETCH_ASSOC);
        foreach ($encrypted_row as $key => $value) {
            if (ctype_print($value)) {
                print "Error: expected a binary array for $key\n";
            }
        }
        unset($stmt);
        unset($conn1);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
dropTable($conn, $tbname);
unset($conn);
echo "Done\n";
?>
--EXPECT--
Retrieving plaintext data:
SSN: 795-73-9838
FirstName: Catherine
LastName: Abel
BirthDate: 1996-10-19

Checking ciphertext data:
Done
