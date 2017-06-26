--TEST--
Test the different type of data for retrieving
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once 'MsCommon.inc';

try
{
    $db = connect();
    
    
    $query = "INSERT INTO PDO_AllTypes (";
    $query .= "BigIntCol,BitCol,IntCol,";
    $query .= "SmallIntCol,TinyIntCol,";
    $query .= "DecimalCol,NumCol,MoneyCol,";
    $query .= "SmallMoneyCol,FloatCol,RealCol,";
    $query .= "CharCol,VarcharCol,TextCol,";
    $query .= "NCharCol,NVarcharCol,ImageCol,";
    $query .= "BinaryCol,VarbinaryCol,SmallDTCol,";
    $query .= "DateTimeCol,DTOffsetCol,";
    $query .= "TimeCol,Guidcol,VarbinaryMaxCol,";
    $query .= "VarcharMaxCol,XmlCol,NTextCol,";
    $query .= "NVarCharMaxCol) VALUES (1,'0',1,1,1,111,1,";
    $query .= "111.1110,111.1110,111.111,111.111,";
    $query .= "'STRINGCOL1','STRINGCOL1',";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',";
    $query .= "'STRINGCOL1','STRINGCOL1','00',";
    $query .= "CONVERT(BINARY(2),'0000000000',2),CONVERT(VARBINARY(2),CAST('00' AS VARCHAR(2)),2),'2000-11-11 11:11:00',";
    $query .= "'2000-11-11 11:11:11.110',";
    $query .= "'2000-11-11 11:11:11.1110000 +00:00','11:11:11.1110000',";
    $query .= "'AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA',CONVERT(VARBINARY(MAX),CAST('00' AS VARCHAR(MAX)),2) ,";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',";
    $query .= "'<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>',";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.')";
    $numRows = $db->exec($query);
    
    echo "Insert complete!";
}
catch(PDOException $e)
{
    var_dump($e);
}
?>

--EXPECT--
Insert complete!