<?php
/*
    Microsoft SQL Server Driver for PHP - Unit Test Framework
    Copyright (c) Microsoft Corporation.  All rights reserved.

    Description:
        Random data generator for INSERT statements.

*/

// globals
$int_col = array(1, 2);

$bin = fopen('php://memory', 'a');
fwrite($bin, '00');
rewind($bin);
$binary_col = array($bin, $bin);

$string_col = array("STRINGCOL1", "STRINGCOL2");

$decimal_col = array(111.111, 222.222);

$large_string = "This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.";

$date_col = array('2000-11-11', '2000-11-11');
$datetime_col = array('2000-11-11 11:11:11.111', '2000-11-11 11:11:11.222');
$datetime2_col = array('2000-11-11 11:11:11.111', '2000-11-11 11:11:11.222');
$dtoffset_col = array('2000-11-11 11:11:11.111 +00:00', '2000-11-11 11:11:11.222 +00:00');
$smalldt_col = array('2000-11-11 11:11:00', '2000-11-11 11:11:00');
$timeCol = array('11:11:11.111', '11:11:11.222');

$large_string_col = array(" 1 " . $large_string . "", " 2 " . $large_string . "");

$guid_col = array("AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA", "BBBBBBBB-BBBB-BBBB-BBBB-BBBBBBBBBBBB");

$xml_col = array("<xml> 1 " . $large_string . "</xml>", "<xml> 2 " . $large_string . "</xml>");

$null_col = 'null';

$mainTypesClass = 'PDO_MainTypesClass';
class PDO_MainTypesClass
{
    public $IntCol;
    public $CharCol;
    public $NCharCol;
    public $DateTimeCol;
    public $VarcharCol;
    public $NVarCharCol;
    public $FloatCol;
    public $XmlCol;

    public function dumpAll()
    {
        var_dump($this->IntCol);
        var_dump($this->CharCol);
        var_dump($this->NCharCol);
        var_dump($this->DateTimeCol);
        var_dump($this->VarcharCol);
        var_dump($this->NVarCharCol);
        var_dump($this->FloatCol);
        var_dump($this->XmlCol);
    }
}

function createTableMainTypes($conn, $tbname)
{
    try {
        require_once("MsCommon_mid-refactor.inc");
        $columnMetaArr = array("IntCol" => "int",
                               "CharCol" => "char(10)",
                               "NCharCol" => "nchar(10)",
                               "DateTimeCol" => "datetime",
                               "VarcharCol" => "varchar(50)",
                               "NVarCharCol" => "nvarchar(50)",
                               "FloatCol" => "float",
                               "XmlCol" => "xml");
        createTable($conn, $tbname, $columnMetaArr, "ON [PRIMARY]");
    } catch (Exception $e) {
        var_dump($e);
        exit;
    }
}

function insertIntoTableMainTypes($conn, $tbname, $rownum)
{
    try {
        require_once("MsCommon_mid-refactor.inc");
        global $string_col, $date_col, $large_string_col, $xml_col, $binary_col, $int_col, $decimal_col, $guid_col, $datetime_col, $datetime2_col, $dtoffset_col, $smalldt_col, $timeCol;

        $inputs = array("IntCol" => $int_col[$rownum],
                        "CharCol" => $string_col[$rownum],
                        "NCharCol" => $string_col[$rownum],
                        "DateTimeCol" => $datetime_col[$rownum],
                        "VarcharCol" => $string_col[$rownum],
                        "NVarCharCol" => $string_col[$rownum],
                        "FloatCol" => $decimal_col[$rownum],
                        "XmlCol" => $xml_col[$rownum]);
        $stmt = insertRow($conn, $tbname, $inputs, "prepareBindParam");
    } catch (Exception $e) {
        var_dump($e);
        exit;
    }
}

function createAndInsertTableMainTypes($conn, $tbname, $rows = array(0, 1))
{
    createTableMainTypes($conn, $tbname);
    foreach ($rows as $rownum) {
        insertIntoTableMainTypes($conn, $tbname, $rownum);
    }
}

function createTableAllTypes($conn, $tbname)
{
    try {
        require_once("MsCommon_mid-refactor.inc");
        $columnMetaArr = array("BigIntCol" => "bigint",
                               "BinaryCol" => "binary(5)",
                               "BitCol" => "bit",
                               "CharCol" => "char(10)",
                               "DateCol" => "date",
                               "DateTimeCol" => "datetime",
                               "DateTime2Col" => "datetime2(7)",
                               "DTOffsetCol" => "datetimeoffset(7)",
                               "DecimalCol" => "decimal(18,0)",
                               "FloatCol" => "float",
                               "ImageCol" => "image",
                               "IntCol" => "int",
                               "MoneyCol" => "money",
                               "NCharCol" => "nchar(10)",
                               "NTextCol" => "ntext",
                               "NumCol" => "numeric(18,0)",
                               "NVarCharCol" => "nvarchar(50)",
                               "NVarCharMaxCol" => "nvarchar(max)",
                               "RealCol" => "real",
                               "SmallDTCol" => "smalldatetime",
                               "SmallIntCol" => "smallint",
                               "SmallMoneyCol" => "smallmoney",
                               "TextCol" => "text",
                               "TimeCol" => "time(7)",
                               "TinyIntCol" => "tinyint",
                               "Guidcol" => "uniqueidentifier",
                               "VarbinaryCol" => "varbinary(50)",
                               "VarbinaryMaxCol" => "varbinary(max)",
                               "VarcharCol" => "varchar(50)",
                               "VarcharMaxCol" => "varchar(max)",
                               "XmlCol" => "xml");
        createTable($conn, $tbname, $columnMetaArr, "ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]");
    } catch (Exception $e) {
        var_dump($e);
        exit;
    }
}

function insertIntoTableAllTypes($conn, $tbname, $rownum)
{
    try {
        require_once("MsCommon_mid-refactor.inc");
        global $string_col, $date_col, $large_string_col, $xml_col, $binary_col, $int_col, $decimal_col, $guid_col, $datetime_col, $datetime2_col, $dtoffset_col, $smalldt_col, $timeCol;

        $inputs = array("BigIntCol" => $int_col[$rownum],
                        "BinaryCol" => new BindParamOp(2, $binary_col[$rownum], "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                        "BitCol" => 0,
                        "CharCol" => $string_col[$rownum],
                        "DateCol" => $date_col[$rownum],
                        "DateTimeCol" => $datetime_col[$rownum],
                        "DateTime2Col" => $datetime2_col[$rownum],
                        "DTOffsetCol" => $dtoffset_col[$rownum],
                        "DecimalCol" => $decimal_col[$rownum],
                        "FloatCol" => $decimal_col[$rownum],
                        "ImageCol" => new BindParamOp(11, $binary_col[$rownum], "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                        "IntCol" => $int_col[$rownum],
                        "MoneyCol" => $decimal_col[$rownum],
                        "NCharCol" => $string_col[$rownum],
                        "NTextCol" => $large_string_col[$rownum],
                        "NumCol" => $int_col[$rownum],
                        "NVarCharCol" => $string_col[$rownum],
                        "NVarCharMaxCol" => $large_string_col[$rownum],
                        "RealCol" => $decimal_col[$rownum],
                        "SmallDTCol" => $smalldt_col[$rownum],
                        "SmallIntCol" => $int_col[$rownum],
                        "SmallMoneyCol" => $decimal_col[$rownum],
                        "TextCol" => $large_string_col[$rownum],
                        "TimeCol" => $timeCol[$rownum],
                        "TinyIntCol" => $int_col[$rownum],
                        "Guidcol" => $guid_col[$rownum],
                        "VarbinaryCol" => new BindParamOp(27, $binary_col[$rownum], "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                        "VarbinaryMaxCol" => new BindParamOp(28, $binary_col[$rownum], "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                        "VarcharCol" => $string_col[$rownum],
                        "VarcharMaxCol" => $large_string_col[$rownum],
                        "XmlCol" => $xml_col[$rownum]);
        $stmt = insertRow($conn, $tbname, $inputs, "prepareBindParam");
    } catch (Exception $e) {
        var_dump($e);
        exit;
    }
}

function createAndInsertTableAllTypes($conn, $tbname, $rows = array(0))
{
    createTableAllTypes($conn, $tbname);
    foreach ($rows as $rownum) {
        insertIntoTableAllTypes($conn, $tbname, $rownum);
    }
}
