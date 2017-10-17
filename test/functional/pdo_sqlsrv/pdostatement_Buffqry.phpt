--TEST--
PDOStatement::BindParam for predefined constants and buffered query.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

//*************************************************************************************

// Test binding with different predefined constants and using buffered query to update and

// select data.

//*************************************************************************************

require_once 'MsCommon.inc';

function insert($db)
{
    $query = "DECLARE @string VARCHAR(MAX) = '0000000000'";
    $query .= "DECLARE @string1 VARCHAR(MAX) = '00'";
    $query .= "DECLARE @bin VARBINARY(MAX)";
    $query .= "DECLARE @bin1 VARBINARY(2)";
    $query .= "SET @bin = CAST(@string AS VARBINARY(MAX))";
    $query .= "SET @bin1 = CAST(@string1 AS VARBINARY(2))";
    $query .= "INSERT INTO PDO_AllTypes (";
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
    $query .= "NVarCharMaxCol) VALUES (0,'0',0,1,1,111,1,";
    $query .= "111.1110,111.1110,111.111,111.111,";
    $query .= "'STRINGCOL2','STRINGCOL2',";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',";
    $query .= "'STRINGCOL2','STRINGCOL2','00',";
    $query .= "CONVERT(BINARY(2),@bin),CONVERT(VARBINARY(2),@bin1),'2000-11-11 11:11:00',";
    $query .= "'2000-11-11 11:11:11.110',";
    $query .= "'2000-11-11 11:11:11.1110000 +00:00','11:11:11.1110000',";
    $query .= "'AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA',CONVERT(VARBINARY(MAX),@bin1) ,";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',";
    $query .= "'<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>',";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.',";
    $query .= "'1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.')";

    $numRows = $db->exec($query);

    echo "Insert complete!\n";
}

function bindParam_LOB($db)
{
    // Binding LOB with buffered queries activated.
    $noteID = fopen('php://memory', 'a');
    $data = '0';
    $query = "UPDATE PDO_AllTypes SET [VarbinaryCol]=:Name WHERE [BitCol]=:value";
    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    fwrite($noteID, '00');
    rewind($noteID);
    $stmt->bindParam(':Name', $noteID, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(':value', $data);
    $result = $stmt->execute();

    $select = "SELECT * FROM PDO_AllTypes WHERE [BitCol]=:value";
    $stmt = $db->prepare($select, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->bindParam(':value', $data);
    $stmt->execute();
    $result = $stmt->fetchColumn(26);    // Retrieve VarbinaryCol
    print("$result\n");
}

function bindParam_STR($db)
{
    // Binding STR with buffered queries activated.
    $noteID = fopen('php://memory', 'a');
    $data = 'STRINGCOL2';
    $query = "UPDATE PDO_AllTypes SET [BitCol]=:Name WHERE [CharCol]=:value";
    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    fwrite($noteID, '1');
    rewind($noteID);
    $stmt->bindParam(':Name', $noteID, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(':value', $data);
    $stmt->execute();

    $select = "SELECT * FROM PDO_AllTypes WHERE [CharCol]=:value";
    $stmt = $db->prepare($select, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->bindParam(':value', $data);
    $stmt->execute();
    $result = $stmt->fetchColumn(2);    // Retrieve BitCol
    $result = str_replace("\0", "", $result);
    print("$result\n");
}

function bindParam_NULL($db)
{
    // Binding NULL with buffered queries activated.
    $noteID = fopen('php://memory', 'a');
    $data = 'STRINGCOL2';
    $query = "UPDATE PDO_AllTypes SET [BitCol]=:Name WHERE [VarcharCol]=:value";

    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    fwrite($noteID, null);
    rewind($noteID);
    $stmt->bindParam(':Name', $noteID, PDO::PARAM_NULL);
    $stmt->bindParam(':value', $data);
    $stmt->execute();

    $data = '0';
    $select = "SELECT * FROM PDO_AllTypes WHERE [BitCol]=:value";
    $stmt = $db->prepare($select, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->bindParam(':value', $data);
    $stmt->execute();
    $result = $stmt->fetchColumn(2);    // Retrieve BitCol
    $result = str_replace("\0", "", $result);
    print("$result\n");
}

function bindParam_INT($db)
{
    // Binding INT with buffered queries activated.
    $noteID = fopen('php://memory', 'a');
    $data = 0;
    $query = "UPDATE PDO_ALLTypes SET [IntCol]=:Name WHERE [IntCol]=:value";
    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    fwrite($noteID, '1');
    rewind($noteID);
    $stmt->bindParam(':Name', $noteID, PDO::PARAM_INT);
    $stmt->bindParam(':value', $data);
    $stmt->execute();

    $select = "SELECT * FROM PDO_AllTypes WHERE [BigIntCol]=:value";
    $stmt = $db->prepare($select, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->bindParam(':value', $data, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn(11);    // Retrieve IntCol
    $result = str_replace("\0", "", $result);
    print("$result\n");
}

function bindParam_BOOL($db)
{
    // Binding BOOL with buffered queries activated.
    $noteID = fopen('php://memory', 'a');
    $data = '0';
    $query = "UPDATE PDO_AllTypes SET [BitCol]=:Name WHERE [BigIntCol]=:value";
    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    fwrite($noteID, '1');
    rewind($noteID);
    $stmt->bindParam(':Name', $noteID, PDO::PARAM_BOOL);
    $stmt->bindParam(':value', $data);
    $stmt->execute();


    $query = "SELECT * FROM PDO_AllTypes WHERE [BigIntCol]=:value";
    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->bindParam(':value', $data, PDO::PARAM_BOOL);
    $stmt->execute();
    $result = $stmt->fetchColumn(2);    // Retrieve BitCol
    $result = str_replace("\0", "", $result);
    print("$result\n");
}

function delete($db)
{
    $data = "STRINGCOL2";
    $query = "DELETE FROM PDO_AllTypes WHERE [CharCol]=:value";
    $stmt = $db->prepare($query, array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->bindParam(':value', $data);
    $stmt->execute();
}

try {
    $db = connect();
    insert($db);
    bindParam_LOB($db);
    bindParam_STR($db);
    bindParam_NULL($db);
    bindParam_INT($db);
    bindParam_BOOL($db);
    delete($db);

    echo "Test Completed";
} catch (PDOException $e) {
    var_dump($e);
    exit;
}

?>

--EXPECT--
Insert complete!
00
1
0
1
1
Test Completed
