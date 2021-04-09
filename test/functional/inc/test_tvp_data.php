<?php

$createTVPOrd = <<<ORD
CREATE TABLE TVPOrd(
    OrdNo INTEGER IDENTITY(1,1), 
    OrdDate DATETIME, 
    CustID VARCHAR(10))
ORD;

$createTVPItem = <<<ITEM
CREATE TABLE TVPItem(
    OrdNo INTEGER, 
    ItemNo INTEGER IDENTITY(1,1), 
    ProductCode CHAR(10), 
    OrderQty INTEGER, 
    SalesDate DATE, 
    Label VARCHAR(30), 
    Price DECIMAL(5,2), 
    Photo VARBINARY(MAX))
ITEM;

$createTVPParam = <<<TYPE
CREATE TYPE TVPParam AS TABLE(
                ProductCode CHAR(10), 
                OrderQty INTEGER, 
                SalesDate DATE, 
                Label VARCHAR(30), 
                Price DECIMAL(5,2), 
                Photo VARBINARY(MAX))
TYPE;

$createTVPOrderEntry = <<<PROC
CREATE PROCEDURE TVPOrderEntry(
        @CustID VARCHAR(10), 
        @Items TVPParam READONLY,
        @OrdNo INTEGER OUTPUT, 
        @OrdDate DATETIME OUTPUT)
AS
BEGIN
    SET @OrdDate = GETDATE(); SET NOCOUNT ON; 
    INSERT INTO TVPOrd (OrdDate, CustID) VALUES (@OrdDate, @CustID);
    SELECT @OrdNo = SCOPE_IDENTITY();
    INSERT INTO TVPItem (OrdNo, ProductCode, OrderQty, SalesDate, Label, Price, Photo)
    SELECT @OrdNo, ProductCode, OrderQty, SalesDate, Label, Price, Photo 
    FROM @Items
END;
PROC;

$callTVPOrderEntry = "{call TVPOrderEntry(?, ?, ?, ?)}";
$callTVPOrderEntryNamed = "{call TVPOrderEntry(:id, :tvp, :ordNo, :ordDate)}";

$gif1 = 'awc_tee_male_large.gif';
$gif2 = 'superlight_black_f_large.gif';
$gif3 = 'silver_chain_large.gif';

$items = [
    ['0062836700', 367, date_create("2009-03-12"), 'AWC Tee Male Shirt', '20.75'],
    ['1250153272', 256, date_create("2017-11-07"), 'Superlight Black Bicycle', '998.45'],
    ['1328781505', 260, date_create("2010-03-03"), 'Silver Chain for Bikes', '88.98'],
];

$selectTVPItemQuery = 'SELECT OrdNo, ItemNo, ProductCode, OrderQty, SalesDate, Label, Price FROM TVPItem ORDER BY ItemNo';

///////////////////////////////////////////////////////

$createTestTVP = <<<TYPE1
CREATE TYPE TestTVP AS TABLE(
                c01 VARCHAR(255),
                c02 VARCHAR(MAX),
                c03 VARBINARY(255),
                c04 VARBINARY(MAX),
                c05 BIT,
                c06 DATE,
                c07 TIME,
                c08 DATETIME2(5),
                c09 BIGINT,
                c10 FLOAT,
                c11 NUMERIC(38, 24),
                c12 UNIQUEIDENTIFIER)
TYPE1;

$createSelectTVP = <<<PROC1
CREATE PROCEDURE SelectTVP (
        @TVP TestTVP READONLY) 
        AS 
        SELECT * FROM @TVP
PROC1;

///////////////////////////////////////////////////////
// Common functions
///////////////////////////////////////////////////////

function dropTableTypeSQL($conn, $typeName)
{
    return "IF EXISTS (SELECT * FROM sys.types WHERE is_table_type = 1 AND name = '$typeName') DROP TYPE $typeName";
}

function verifyBinaryData($fp, $data)
{
    $size = 8192;
    $pos = 0;
    $matched = true;
    while (!feof($fp)) {
        $original = fread($fp, $size);
        $str = substr($data, $pos, $size);
        
        if ($original !== $str) {
            $matched = false;
            break;
        }
        $pos += $size;
    }
    
    return $matched;
}

function verifyBinaryStream($fp, $stream)
{
    $size = 8192;
    $matched = true;
    while (!feof($fp) && !feof($stream)) {
        $original = fread($fp, $size);
        $data = fread($stream, $size);
        
        if ($original !== $data) {
            $matched = false;
            break;
        }
    }
    
    return $matched;
}

?>