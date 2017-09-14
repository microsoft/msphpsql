--TEST--
Test for inserting encrypted fixed size types data and retrieve both encrypted and decrypted data
--SKIPIF--

--FILE--
<?php
include 'MsCommon.inc';
include 'MsSetup.inc';
include 'AEData.inc';

$conn = ae_connect();
$testPass = true;

// Create the table
$tbname = 'FixedSizeAnalysis';
$colMetaArr = array( new columnMeta( "tinyint", "TinyIntData" ), 
                     new columnMeta( "smallint", "SmallIntData" ), 
                     new columnMeta( "int", "IntData" ), 
                     new columnMeta( "bigint", "BigIntData" ), 
                     new columnMeta( "decimal(38,0)", "DecimalData" ), 
                     new columnMeta( "bit", "BitData" ), 
                     new columnMeta( "datetime", "DateTimeData" ), 
                     new columnMeta( "datetime2", "DateTime2Data" ));
create_table( $conn, $tbname, $colMetaArr );

// insert a row
$inputs = array( "TinyIntData" => 255, 
                 "SmallIntData" => 32767, 
                 "IntData" => 2147483647, 
                 "BigIntData" => 9223372036854774784, 
                 "DecimalData" => 79228162514264, 
                 "BitData" => true, 
                 "DateTimeData" => '9999-12-31 23:59:59.997', 
                 "DateTime2Data" => '9999-12-31 23:59:59.9999999');
$stmt = insert_row( $conn, $tbname, $inputs );

print "Decrypted values:\n";
fetch_all( $conn, $tbname );

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );

// for AE only
if ( $keystore != "none" )
{
    $conn = connect( null, true );
   
    print "\nEncrypted values:\n";
    $selectSql = "SELECT * FROM $tbname";
    $stmt = sqlsrv_query( $conn, $selectSql );
    $encrypted_row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC );
    foreach( $encrypted_row as $key => $value )
    {
        if ( !ctype_print( $value ))
        {
            print "Binary array returned for $key\n";
        }
    }

    DropTable( $conn, $tbname );
    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );
}
?>
--EXPECT--
Decrypted values:
TinyIntData: 255
SmallIntData: 32767
IntData: 2147483647
BigIntData: 9223372036854774784
DecimalData: 79228162514264
BitData: 1
DateTimeData:
  date: 9999-12-31 23:59:59.997000
  timezone_type: 3
  timezone: UTC
DateTime2Data:
  date: 9999-12-31 23:59:59.1000000
  timezone_type: 3
  timezone: UTC

Encrypted values:
Binary array returned for TinyIntData
Binary array returned for SmallIntData
Binary array returned for IntData
Binary array returned for BigIntData
Binary array returned for DecimalData
Binary array returned for BitData
Binary array returned for DateTimeData
Binary array returned for DateTime2Data