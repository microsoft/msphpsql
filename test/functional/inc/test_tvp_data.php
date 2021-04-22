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
    PackedOn DATE, 
    Label NVARCHAR(30), 
    Price DECIMAL(5,2), 
    Photo VARBINARY(MAX))
ITEM;

$createTVPParam = <<<TYPE
CREATE TYPE TVPParam AS TABLE(
                ProductCode CHAR(10), 
                OrderQty INTEGER, 
                PackedOn DATE, 
                Label NVARCHAR(30), 
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
    INSERT INTO TVPItem (OrdNo, ProductCode, OrderQty, PackedOn, Label, Price, Photo)
    SELECT @OrdNo, ProductCode, OrderQty, PackedOn, Label, Price, Photo 
    FROM @Items
END;
PROC;

$callTVPOrderEntry = "{call TVPOrderEntry(?, ?, ?, ?)}";
$callTVPOrderEntryNamed = "{call TVPOrderEntry(:id, :tvp, :ordNo, :ordDate)}";

// The following gif files are some random product pictures 
// retrieved from the AdventureWorks sample database (their 
// sizes ranging from 12 KB to 26 KB)
$gif1 = 'awc_tee_male_large.gif';
$gif2 = 'superlight_black_f_large.gif';
$gif3 = 'silver_chain_large.gif';

$items = [
    ['0062836700', 367, "2009-03-12", 'AWC Tee Male Shirt', '20.75'],
    ['1250153272', 256, "2017-11-07", 'Superlight Black Bicycle', '998.45'],
    ['1328781505', 260, "2010-03-03", 'Silver Chain for Bikes', '88.98'],
];

$selectTVPItemQuery = 'SELECT OrdNo, ItemNo, ProductCode, OrderQty, PackedOn, Label, Price FROM TVPItem ORDER BY ItemNo';

///////////////////////////////////////////////////////

$createTestTVP = <<<TYPE1
CREATE TYPE TestTVP AS TABLE(
                C01 VARCHAR(255),
                C02 VARCHAR(MAX),
                C03 BIT,
                C04 SMALLDATETIME,
                C05 DATETIME2(5),
                C06 UNIQUEIDENTIFIER,
                C07 BIGINT,
                C08 FLOAT,
                C09 NUMERIC(38, 24))
TYPE1;

$createSelectTVP = <<<PROC1
CREATE PROCEDURE SelectTVP (
        @TVP TestTVP READONLY) 
        AS 
        SELECT * FROM @TVP
PROC1;

$callSelectTVP = "{call SelectTVP(?)}";

///////////////////////////////////////////////////////

$createTestTVP2 = <<<TYPE2
CREATE TYPE TestTVP2 AS TABLE(
                C01 NVARCHAR(50),
                C02 NVARCHAR(MAX),
                C03 INT,
                C04 REAL,
                C05 VARBINARY(10),
                C06 VARBINARY(MAX),
                C07 MONEY,
                C08 XML,
                C09 SQL_VARIANT)
TYPE2;

$createSelectTVP2 = <<<PROC2
CREATE PROCEDURE SelectTVP2 (
        @TVP TestTVP2 READONLY) 
        AS 
        SELECT * FROM @TVP
PROC2;

$callSelectTVP2 = "{call SelectTVP2(?)}";

///////////////////////////////////////////////////////
// Use schema other than DBO
///////////////////////////////////////////////////////

$createSchema = 'CREATE SCHEMA [Sales DB]';
$dropSchema = 'DROP SCHEMA IF EXISTS [Sales DB]';

$createTestTVP3 = <<<TYPE3
CREATE TYPE [Sales DB].[TestTVP3] AS TABLE(
    Review VARCHAR(100) NOT NULL,
    SupplierId INT,
    SalesDate DATETIME2 NULL
)
TYPE3;

$createSelectTVP3 = <<<PROC3
CREATE PROCEDURE [Sales DB].[SelectTVP3] (
        @TVP TestTVP3 READONLY )
        AS 
        SELECT * FROM @TVP
PROC3;

$callSelectTVP3 = "{call [Sales DB].[SelectTVP3](?)}";

$createSupplierType = <<<SUPP_TYPE
CREATE TYPE [Sales DB].[SupplierType] AS TABLE(
    SupplierId INT,
    SupplierName NVARCHAR(50)
)
SUPP_TYPE;

$createAddReview = <<<SUPP_PROC
CREATE PROCEDURE [Sales DB].[AddReview] (
    @suppType SupplierType READONLY,
    @reviewType TestTVP3 READONLY,
    @image VARBINARY(MAX))
    AS
    SELECT * FROM @suppType;
    SELECT SupplierId, SalesDate, Review FROM @reviewType;
    SELECT @image
SUPP_PROC;

$callAddReview = "{call [Sales DB].[AddReview](?, ?, ?)}";

///////////////////////////////////////////////////////
// Common functions
///////////////////////////////////////////////////////

function dropProcSQL($conn, $procName)
{
    return "IF OBJECT_ID('$procName', 'P') IS NOT NULL DROP PROCEDURE $procName";
}

function dropTableTypeSQL($conn, $typeName, $schema = 'dbo')
{
    return "IF EXISTS (SELECT * FROM sys.types WHERE is_table_type = 1 AND name = '$typeName') DROP TYPE [$schema].[$typeName]";
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