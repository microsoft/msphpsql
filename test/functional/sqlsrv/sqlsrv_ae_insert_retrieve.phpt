--TEST--
Test for inserting encrypted data and retrieving both encrypted and decrypted data
--DESCRIPTION--
Retrieving SQL query contains encrypted filter
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

date_default_timezone_set("Canada/Pacific");
$conn = AE\connect();

// Create the table
$tbname = 'Patients';
$colMetaArr = array( new AE\ColumnMeta("int", "PatientId", "IDENTITY(1,1)"),
                         new AE\ColumnMeta("char(11)", "SSN"),
                         new AE\ColumnMeta("nvarchar(50)", "FirstName", "NULL"),
                         new AE\ColumnMeta("nvarchar(50)", "LastName", "NULL"),
                         new AE\ColumnMeta("date", "BirthDate", null, false));
AE\createTable($conn, $tbname, $colMetaArr);

// insert a row
$SSN = "795-73-9838";
$inputs = array( "SSN" => $SSN, "FirstName" => "Catherine", "LastName" => "Abel", "BirthDate" => "1996-10-19" );
$stmt = AE\insertRow($conn, $tbname, $inputs);

echo "Retrieving plaintext data:\n";
$selectSql = "SELECT SSN, FirstName, LastName, BirthDate FROM $tbname WHERE SSN = ?";
$stmt = sqlsrv_prepare($conn, $selectSql, array( $SSN ));
sqlsrv_execute($stmt);
$decrypted_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
foreach ($decrypted_row as $key => $value) {
    if (!is_object($value)) {
        print "$key: $value\n";
    } else {
        print "$key:\n";
        foreach ($value as $dateKey => $dateValue) {
            print "  $dateKey: $dateValue\n";
        }
    }
}
sqlsrv_free_stmt($stmt);

//for AE only
echo "\nChecking ciphertext data:\n";
if (AE\isDataEncrypted()) {
    $conn1 = connect(null, true);
    $selectSql = "SELECT SSN, FirstName, LastName, BirthDate FROM $tbname";
    $stmt = sqlsrv_query($conn1, $selectSql);
    $encrypted_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    foreach ($encrypted_row as $key => $value) {
        if (ctype_print($value)) {
            print "Error: expected a binary array for $key!\n";
        }
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn1);
}

dropTable($conn, $tbname);
sqlsrv_close($conn);

echo "Done";

?>
--EXPECT--
Retrieving plaintext data:
SSN: 795-73-9838
FirstName: Catherine
LastName: Abel
BirthDate:
  date: 1996-10-19 00:00:00.000000
  timezone_type: 3
  timezone: Canada/Pacific

Checking ciphertext data:
Done
