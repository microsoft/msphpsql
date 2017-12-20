--TEST--
Test for inserting encrypted fixed size types data and retrieve both encrypted and decrypted data
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

date_default_timezone_set("Canada/Pacific");
$conn = AE\connect();
$testPass = true;

// Create the table
$tbname = 'FixedSizeAnalysis';
$colMetaArr = array( new AE\ColumnMeta("tinyint", "TinyIntData"),
                     new AE\ColumnMeta("smallint", "SmallIntData"),
                     new AE\ColumnMeta("int", "IntData"),
                     new AE\ColumnMeta("bigint", "BigIntData"),
                     new AE\ColumnMeta("decimal(38,0)", "DecimalData"),
                     new AE\ColumnMeta("bit", "BitData"),
                     new AE\ColumnMeta("datetime", "DateTimeData"),
                     new AE\ColumnMeta("datetime2", "DateTime2Data"));
AE\createTable($conn, $tbname, $colMetaArr);

// insert a row
$inputs = array( "TinyIntData" => 255,
                 "SmallIntData" => 32767,
                 "IntData" => 2147483647,
                 "BigIntData" => 92233720368547,
                 "DecimalData" => 79228162514264,
                 "BitData" => 1,
                 "DateTimeData" => '9999-12-31 23:59:59.997',
                 "DateTime2Data" => '9999-12-31 23:59:59.123456');
$r;
$stmt = AE\insertRow($conn, $tbname, $inputs, $r);
if ($r === false) {
    var_dump(sqlsrv_errors());
}

print "Decrypted values:\n";
AE\fetchAll($conn, $tbname);

sqlsrv_free_stmt($stmt);

// for AE only
if (AE\isDataEncrypted()) {
    $conn1 = connect(null, true);

    $selectSql = "SELECT * FROM $tbname";
    $stmt = sqlsrv_query($conn1, $selectSql);
    if ($stmt === false) {
        var_dump(sqlsrv_errors());
    }
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

echo "Done\n";

?>
--EXPECT--
Decrypted values:
TinyIntData: 255
SmallIntData: 32767
IntData: 2147483647
BigIntData: 92233720368547
DecimalData: 79228162514264
BitData: 1
DateTimeData:
  date: 9999-12-31 23:59:59.997000
  timezone_type: 3
  timezone: Canada/Pacific
DateTime2Data:
  date: 9999-12-31 23:59:59.123456
  timezone_type: 3
  timezone: Canada/Pacific
Done
