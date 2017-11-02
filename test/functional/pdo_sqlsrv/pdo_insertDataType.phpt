--TEST--
Test the different type of data for retrieving
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_AllTypes";
    createTableAllTypes($db, $tbname);

    $bin = fopen('php://memory', 'a');
    fwrite($bin, '00');
    rewind($bin);
    $inputs = array("BigIntCol" => 0,
                    "BitCol" => '0',
                    "IntCol" => 1,
                    "SmallIntCol" => 1,
                    "TinyIntCol" => 1,
                    "DecimalCol" => 111,
                    "NumCol" => 1,
                    "MoneyCol" => 111.1110,
                    "SmallMoneyCol" => 111.1110,
                    "FloatCol" => 111.111,
                    "RealCol" => 111.111,
                    "CharCol" => 'STRINGCOL1',
                    "VarcharCol" => 'STRINGCOL1',
                    "TextCol" => '1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',
                    "NCharCol" => 'STRINGCOL1',
                    "NVarcharCol" => 'STRINGCOL1',
                    "ImageCol" => new BindParamOp(17, $bin, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                    "BinaryCol" => new BindParamOp(18, $bin, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                    "VarbinaryCol" => new BindParamOp(19, $bin, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                    "SmallDTCol" => '2000-11-11 11:11:00',
                    "DateTimeCol" => '2000-11-11 11:11:11.110',
                    "DTOffsetCol" => '2000-11-11 11:11:11.1110000 +00:00',
                    "TimeCol" => '11:11:11.1110000',
                    "Guidcol" => 'AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA',
                    "VarbinaryMaxCol" => new BindParamOp(25, $bin, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                    "VarcharMaxCol" => '1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',
                    "XmlCol" => '<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>',
                    "NTextCol" => '1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',
                    "NVarCharMaxCol" => '1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.');
    $stmt = insertRow($db, $tbname, $inputs, "prepareBindParam");

    dropTable($db, $tbname);
    unset($stmt);
    unset($conn);
    echo "Insert complete!\n";
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Insert complete!
