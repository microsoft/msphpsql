--TEST--
Test fetchObject method by retrieve all data types.
--SKIPIF--
<?php require("skipif_mid-refactor.inc"); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_AllTypes";
    createAndInsertTableAllTypes($db, $tbname);
    $sql = "SELECT * FROM $tbname";
    $stmt = $db->query($sql);

    $obj = $stmt->fetch(PDO::FETCH_OBJ);
    echo $obj->BigIntCol . "\n";
    echo $obj->BinaryCol . "\n";
    echo $obj->BitCol . "\n";
    echo $obj->CharCol . "\n";
    echo $obj->DateCol . "\n";
    echo $obj->DateTimeCol . "\n";
    echo $obj->DateTime2Col . "\n";
    echo $obj->DTOffsetCol . "\n";
    echo $obj->DecimalCol . "\n";
    echo $obj->FloatCol . "\n";
    echo $obj->ImageCol . "\n";
    echo $obj->IntCol . "\n";
    echo $obj->MoneyCol . "\n";
    echo $obj->NCharCol . "\n";
    echo $obj->NTextCol . "\n";
    echo $obj->NumCol . "\n";
    echo $obj->NVarCharCol . "\n";
    echo $obj->NVarCharMaxCol . "\n";
    echo $obj->RealCol . "\n";
    echo $obj->SmallDTCol . "\n";
    echo $obj->SmallIntCol . "\n";
    echo $obj->SmallMoneyCol . "\n";
    echo $obj->TextCol . "\n";
    echo $obj->TimeCol . "\n";
    echo $obj->TinyIntCol . "\n";
    echo $obj->Guidcol . "\n";
    echo $obj->VarbinaryCol . "\n";
    echo $obj->VarbinaryMaxCol . "\n";
    echo $obj->VarcharCol . "\n";
    echo $obj->VarcharMaxCol . "\n";
    echo $obj->XmlCol . "\n";

    dropTable($db, $tbname);
    unset($stmt);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECTF--
1
abcde
0
STRINGCOL1
2000-11-11
2000-11-11 11:11:11.110
2000-11-11 11:11:11.1110000
2000-11-11 11:11:11.1110000 +00:00
111
111.111%S
abcde
1
111.1110
STRINGCOL1
 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.
1
STRINGCOL1
 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.
111.111
2000-11-11 11:11:00
1
111.1110
 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.
11:11:11.1110000
1
AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA
abcde
abcde
STRINGCOL1
 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.
<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>
