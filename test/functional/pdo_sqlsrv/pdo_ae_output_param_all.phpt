--TEST--
Test for binding output params for encrypted data for all types
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

$conn = ae_connect();

// Create the table
$tbname = GetTempTableName( "", false );
$colMetaArr = array( new columnMeta( "int", "c1_int" ), 
                     new columnMeta( "smallint", "c2_smallint" ), 
                     new columnMeta( "tinyint", "c3_tinyint" ), 
                     new columnMeta( "bit", "c4_bit" ), 
                     new columnMeta( "bigint", "c5_bigint" ), 
                     new columnMeta( "decimal(18,5)", "c6_decimal" ), 
                     new columnMeta( "numeric(10,5)", "c7_numeric" ), 
                     new columnMeta( "float", "c8_float" ), 
                     new columnMeta( "real", "c9_real" ), 
                     new columnMeta( "date", "c10_date" ), 
                     new columnMeta( "datetime", "c11_datetime" ), 
                     new columnMeta( "datetime2", "c12_datetime2" ), 
                     new columnMeta( "datetimeoffset", "c13_datetimeoffset" ), 
                     new columnMeta( "time", "c14_time" ), 
                     new columnMeta( "char(5)", "c15_char" ), 
                     new columnMeta( "varchar(max)", "c16_varchar" ), 
                     new columnMeta( "nchar(5)", "c17_nchar" ), 
                     new columnMeta( "nvarchar(max)", "c18_nvarchar" ));
create_table( $conn, $tbname, $colMetaArr );

// Create a Store Procedure
$spname = 'selectAllColumns';
$spSql = "CREATE PROCEDURE $spname (
                @c1_int int OUTPUT, @c2_smallint smallint OUTPUT, 
                @c3_tinyint tinyint OUTPUT, @c4_bit bit OUTPUT, 
                @c5_bigint bigint OUTPUT, @c6_decimal decimal(18,5) OUTPUT,
                @c7_numeric numeric(10,5) OUTPUT, @c8_float float OUTPUT, 
                @c9_real real OUTPUT, @c10_date date OUTPUT,
                @c11_datetime datetime OUTPUT, @c12_datetime2 datetime2 OUTPUT,
                @c13_datetimeoffset datetimeoffset OUTPUT, @c14_time time OUTPUT,
                @c15_char char(5) OUTPUT, @c16_varchar varchar(max) OUTPUT,
                @c17_nchar nchar(5) OUTPUT, @c18_nvarchar nvarchar(max) OUTPUT) AS
                SELECT @c1_int = c1_int, @c2_smallint = c2_smallint, 
                @c3_tinyint = c3_tinyint, @c4_bit = c4_bit, 
                @c5_bigint = c5_bigint, @c6_decimal = c6_decimal, 
                @c7_numeric = c7_numeric, @c8_float = c8_float, 
                @c9_real = c9_real, @c10_date = c10_date, 
                @c11_datetime = c11_datetime, @c12_datetime2 = c12_datetime2,
                @c13_datetimeoffset = c13_datetimeoffset, @c14_time = c14_time,
                @c15_char = c15_char, @c16_varchar = c16_varchar,
                @c17_nchar = c17_nchar, @c18_nvarchar = c18_nvarchar
                FROM $tbname";
$conn->query( $spSql );

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
$paramOptions = array( new bindParamOption( 5, "PDO::PARAM_INT" ) );
$r;
$stmt = insert_row( $conn, $tbname, $inputs, $r, "prepareBindParam", $paramOptions );

// Call store procedure
$outSql = get_callProcSql_placeholders( $spname, count( $inputs ));
$intOut = 0;
$smallintOut = 0;
$tinyintOut = 0;
$bitOut = 0;
$bigintOut = 0.0;
$decimalOut = 0.0;
$numericOut = 0.0;
$floatOut = 0.0;
$realOut = 0.0;
$dateOut = '0001-01-01';
$datetimeOut = '1753-01-01 00:00:00';
$datetime2Out = '0001-01-01 00:00:00';
$datetimeoffsetOut = '1900-01-01 00:00:00 +01:00';
$timeOut = '00:00:00';
$charOut = '';
$varcharOut = '';
$ncharOut = '';
$nvarcharOut = '';
$stmt = $conn->prepare( $outSql );
$stmt->bindParam(1, $intOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->bindParam(2, $smallintOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->bindParam(3, $tinyintOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->bindParam(4, $bitOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
$stmt->bindParam(5, $bigintOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(6, $decimalOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(7, $numericOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(8, $floatOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(9, $realOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(10, $dateOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(11, $datetimeOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(12, $datetime2Out, PDO::PARAM_STR, 2048);
$stmt->bindParam(13, $datetimeoffsetOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(14, $timeOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(15, $charOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(16, $varcharOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(17, $ncharOut, PDO::PARAM_STR, 2048);
$stmt->bindParam(18, $nvarcharOut, PDO::PARAM_STR, 2048);
$stmt->execute();

print ( "intOut: " . $intOut . "\n" );
print ( "smallintOut: " . $smallintOut . "\n" );
print ( "tinyintOut: " . $tinyintOut . "\n" );
print ( "bitOut: " . $bitOut . "\n" );
print ( "bigintOut: " . $bigintOut . "\n" );
print ( "decimalOut: " . $decimalOut . "\n" );
print ( "numericOut: " . $numericOut . "\n" );
print ( "floatOut: " . $floatOut . "\n" );
print ( "realOut: " . $realOut . "\n" );
print ( "dateOut: " . $dateOut . "\n" );
print ( "datetimeOut: " . $datetimeOut . "\n" );
print ( "datetime2Out: " . $datetime2Out . "\n" );
print ( "datetimeoffsetOut: " . $datetimeoffsetOut . "\n" );
print ( "timeOut: " . $timeOut . "\n" );
print ( "charOut: " . $charOut . "\n" );
print ( "varcharOut: " . $varcharOut . "\n" );
print ( "ncharOut: " . $ncharOut . "\n" );
print ( "nvarcharOut: " . $nvarcharOut . "\n" );

$conn->query( "DROP PROCEDURE $spname" );
$conn->query( "DROP TABLE $tbname" );
unset( $stmt );
unset( $conn );

?>
--EXPECTREGEX--
intOut: 2147483647
smallintOut: 32767
tinyintOut: 255
bitOut: 1
bigintOut: 922337203685479936
decimalOut: 9223372036854\.80000
numericOut: 21474\.83647
floatOut: (9223372036\.8547993|9\.22337e\+009)
realOut: (2147\.4829|2147\.48)
dateOut: 9999-12-31
datetimeOut: (9999-12-31 23:59:59\.997|Dec 31 9999 11:59PM)
datetime2Out: 9999-12-31 23:59:59\.9999999
datetimeoffsetOut: 9999-12-31 23:59:59\.9999999 \+14:00
timeOut: 23:59:59\.9999999
charOut: th\, n
varcharOut: This large row size can cause errors \(such as error 512\) during some normal operations\, such as a clustered index key update\, or sorts of the full column set\, which users cannot anticipate until performing an operation\.
ncharOut: th Un
nvarcharOut: When prefixing a string constant with the letter N\, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type \(4,000\).