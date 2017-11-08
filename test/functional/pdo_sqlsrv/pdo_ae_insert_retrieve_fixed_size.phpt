--TEST--
Test for inserting encrypted fixed size types data and retrieve both encrypted and decrypted data
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
try {
    $conn = connect();
    // Create the table
    $tbname = 'FixedSizeAnalysis';
    $colMetaArr = array("TinyIntData" => "tinyint",
                        "SmallIntData" => "smallint",
                        "IntData" => "int",
                        "BigIntData" => "bigint",
                        "DecimalData" => "decimal(18,0)",
                        "BitData" => "bit",
                        "DateTimeData" => "datetime",
                        "DateTime2Data" => "datetime2");
    createTable($conn, $tbname, $colMetaArr);

    // insert a row
    $inputs = array( "TinyIntData" => 255,
                     "SmallIntData" => 32767,
                     "IntData" => 2147483647,
                     "BigIntData" => 92233720368547,
                     "DecimalData" => 79228162514264,
                     "BitData" => true,
                     "DateTimeData" => '9999-12-31 23:59:59.997',
                     "DateTime2Data" => '9999-12-31 23:59:59.9999999');
    //$paramOptions = array( new bindParamOption(4, "PDO::PARAM_INT") );
    $r;
    $stmt = insertRow($conn, $tbname, $inputs, "prepareBindParam", $r);

    print "Decrypted values:\n";
    fetchAll($conn, $tbname);
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getMessage();
}
// for AE only
if (isColEncrypted()) {
    try {
        $conn1 = connect('', array(), PDO::ERRMODE_EXCEPTION, true);

        $selectSql = "SELECT * FROM $tbname";
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
Decrypted values:
TinyIntData: 255
SmallIntData: 32767
IntData: 2147483647
BigIntData: 92233720368547
DecimalData: 79228162514264
BitData: 1
DateTimeData: 9999-12-31 23:59:59.997
DateTime2Data: 9999-12-31 23:59:59.9999999
Done
