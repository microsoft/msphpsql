--TEST--
Test for binding output parameter of encrypted values for all types
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

// Create the table
$tbname = GetTempTableName("", false);
$colMetaArr = array( new AE\ColumnMeta("int", "c1_int"),
                     new AE\ColumnMeta("smallint", "c2_smallint"),
                     new AE\ColumnMeta("tinyint", "c3_tinyint"),
                     new AE\ColumnMeta("bit", "c4_bit"),
                     new AE\ColumnMeta("bigint", "c5_bigint"),
                     new AE\ColumnMeta("decimal(18,5)", "c6_decimal"),
                     new AE\ColumnMeta("numeric(10,5)", "c7_numeric"),
                     new AE\ColumnMeta("float", "c8_float"),
                     new AE\ColumnMeta("real", "c9_real"),
                     new AE\ColumnMeta("date", "c10_date"),
                     new AE\ColumnMeta("datetime", "c11_datetime"),
                     new AE\ColumnMeta("datetime2", "c12_datetime2"),
                     new AE\ColumnMeta("datetimeoffset", "c13_datetimeoffset"),
                     new AE\ColumnMeta("time", "c14_time"),
                     new AE\ColumnMeta("char(5)", "c15_char"),
                     new AE\ColumnMeta("varchar(max)", "c16_varchar"),
                     new AE\ColumnMeta("nchar(5)", "c17_nchar"),
                     new AE\ColumnMeta("nvarchar(max)", "c18_nvarchar"));
AE\createTable($conn, $tbname, $colMetaArr);

// Create a Store Procedure
$spname = 'selectAllColumns';
createProc($conn, $spname, "@c1_int int OUTPUT, @c2_smallint smallint OUTPUT,
                @c3_tinyint tinyint OUTPUT, @c4_bit bit OUTPUT,
                @c5_bigint bigint OUTPUT, @c6_decimal decimal(18,5) OUTPUT,
                @c7_numeric numeric(10,5) OUTPUT, @c8_float float OUTPUT,
                @c9_real real OUTPUT, @c10_date date OUTPUT,
                @c11_datetime datetime OUTPUT, @c12_datetime2 datetime2 OUTPUT,
                @c13_datetimeoffset datetimeoffset OUTPUT, @c14_time time OUTPUT,
                @c15_char char(5) OUTPUT, @c16_varchar varchar(max) OUTPUT,
                @c17_nchar nchar(5) OUTPUT, @c18_nvarchar nvarchar(max) OUTPUT", "SELECT @c1_int = c1_int, @c2_smallint = c2_smallint,
                @c3_tinyint = c3_tinyint, @c4_bit = c4_bit,
                @c5_bigint = c5_bigint, @c6_decimal = c6_decimal,
                @c7_numeric = c7_numeric, @c8_float = c8_float,
                @c9_real = c9_real, @c10_date = c10_date,
                @c11_datetime = c11_datetime, @c12_datetime2 = c12_datetime2,
                @c13_datetimeoffset = c13_datetimeoffset, @c14_time = c14_time,
                @c15_char = c15_char, @c16_varchar = c16_varchar,
                @c17_nchar = c17_nchar, @c18_nvarchar = c18_nvarchar
                FROM $tbname");
// Insert data
$inputs = array( "c1_int" => 2147483647,
                 "c2_smallint" => 32767,
                 "c3_tinyint" => 255,
                 "c4_bit" => 1,
                 "c5_bigint" => 922337203685479936,
                 "c6_decimal" => 9223372036854.80000,
                 "c7_numeric" => 21474.83647,
                 "c8_float" => 9223372036.8548,
                 "c9_real" => 2147.483,
                 "c10_date" => '9999-12-31',
                 "c11_datetime" => '9999-12-31 23:59:59.997',
                 "c12_datetime2" => '9999-12-31 23:59:59.9999999',
                 "c13_datetimeoffset" => '9999-12-31 23:59:59.9999999 +14:00',
                 "c14_time" => '23:59:59.9999999',
                 "c15_char" => 'th, n',
                 "c16_varchar" => 'This large row size can cause errors (such as error 512) during some normal operations, such as a clustered index key update, or sorts of the full column set, which users cannot anticipate until performing an operation.',
                 "c17_nchar" => 'th Un',
                 "c18_nvarchar" => 'When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).' );
$stmt = AE\insertRow($conn, $tbname, $inputs);

// Call store procedure
$outSql = AE\getCallProcSqlPlaceholders($spname, count($inputs));

$intOut = 0;
$smallintOut = 0;
$tinyintOut = 0;
$bitOut = 0;
$bigintOut = 0.0;
$decimalOut = 0.0;
$numericOut = 0.0;
$floatOut = 0.0;
$realOut = 0.0;
$dateOut = '';
$datetimeOut = '';
$datetime2Out = '';
$datetimeoffsetOut = '';
$timeOut = '';
$charOut = '';
$varcharOut = '';
$ncharOut = '';
$nvarcharOut = '';
$stmt = sqlsrv_prepare($conn, $outSql, array( array( &$intOut, SQLSRV_PARAM_OUT ),
                                        array( &$smallintOut, SQLSRV_PARAM_OUT ),
                                        array( &$tinyintOut, SQLSRV_PARAM_OUT ),
                                        array( &$bitOut, SQLSRV_PARAM_OUT ),
                                        array( &$bigintOut, SQLSRV_PARAM_OUT ),
                                        array( &$decimalOut, SQLSRV_PARAM_OUT ),
                                        array( &$numericOut, SQLSRV_PARAM_OUT ),
                                        array( &$floatOut, SQLSRV_PARAM_OUT ),
                                        array( &$realOut, SQLSRV_PARAM_OUT ),
                                        array( &$dateOut, SQLSRV_PARAM_OUT ),
                                        array( &$datetimeOut, SQLSRV_PARAM_OUT ),
                                        array( &$datetime2Out, SQLSRV_PARAM_OUT ),
                                        array( &$datetimeoffsetOut, SQLSRV_PARAM_OUT ),
                                        array( &$timeOut, SQLSRV_PARAM_OUT ),
                                        array( &$charOut, SQLSRV_PARAM_OUT ),
                                        array( &$varcharOut, SQLSRV_PARAM_OUT ),
                                        array( &$ncharOut, SQLSRV_PARAM_OUT ),
                                        array( &$nvarcharOut, SQLSRV_PARAM_OUT )));
sqlsrv_execute($stmt);

print("intOut: " . $intOut . "\n");
print("smallintOut: " . $smallintOut . "\n");
print("tinyintOut: " . $tinyintOut . "\n");
print("bitOut: " . $bitOut . "\n");
print("bigintOut: " . $bigintOut . "\n");
print("decimalOut: " . $decimalOut . "\n");
print("numericOut: " . $numericOut . "\n");
print("floatOut: " . $floatOut . "\n");
print("realOut: " . $realOut . "\n");
print("dateOut: " . $dateOut . "\n");
print("datetimeOut: " . $datetimeOut . "\n");
print("datetime2Out: " . $datetime2Out . "\n");
print("datetimeoffsetOut: " . $datetimeoffsetOut . "\n");
print("timeOut: " . $timeOut . "\n");
print("charOut: " . $charOut . "\n");
print("varcharOut: " . $varcharOut . "\n");
print("ncharOut: " . $ncharOut . "\n");
print("nvarcharOut: " . $nvarcharOut . "\n");

sqlsrv_query($conn, "DROP PROCEDURE $spname");
sqlsrv_query($conn, "DROP TABLE $tbname");
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECTREGEX--
intOut: 2147483647
smallintOut: 32767
tinyintOut: 255
bitOut: 1
bigintOut: 9.2233720368548E\+17
decimalOut: 9223372036854\.8
numericOut: 21474\.83647
floatOut: 9223372036\.8548
realOut: 2147\.4829101562
dateOut: 9999-12-31
datetimeOut: (9999-12-31 23:59:59\.997|Dec 31 9999 11:59PM)
datetime2Out: 9999-12-31 23:59:59\.9999999
datetimeoffsetOut: 9999-12-31 23:59:59\.9999999 \+14:00
timeOut: 23:59:59\.9999999
charOut: th\, n
varcharOut: This large row size can cause errors \(such as error 512\) during some normal operations\, such as a clustered index key update\, or sorts of the full column set\, which users cannot anticipate until performing an operation\.
ncharOut: th Un
nvarcharOut: When prefixing a string constant with the letter N\, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type \(4,000\)\.
