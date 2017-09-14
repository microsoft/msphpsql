--TEST--
Test for inserting and retrieving encrypted datetime types data
Bind params using sqlsrv_prepare without any sql_type specified
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';
include 'MsSetup.inc';

$dataTypes = array( "date", "datetime", "datetime2", "smalldatetime", "time", "datetimeoffset" );
$conn = ae_connect();

foreach ( $dataTypes as $dataType ) {
    echo "\nTesting $dataType: \n";
    
    // create table
    $tbname = GetTempTableName( "", false );
    $tbname = "test_numeric";
    $colMetaArr = array( new columnMeta( $dataType, "c_det" ), new columnMeta( $dataType, "c_rand" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // insert a row
    $inputValues = array_slice( ${explode( "(", $dataType )[0] . "_params"}, 1, 2 );
    $r;
    $stmt = insert_row( $conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r );
    if ( $r === false ) {
        is_incompatible_types_error( $dataType, "default type" );
    }
    else {
        echo "****Encrypted default type is compatible with encrypted $dataType****\n";
        fetch_all( $conn, $tbname );
    }
    //DropTable( $conn, $tbname );
}
sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--

Testing date:
****Encrypted default type is compatible with encrypted date****
c_det:
  date: 0001-01-01 00:00:00.000000
  timezone_type: 3
  timezone: UTC
c_rand:
  date: 9999-12-31 00:00:00.000000
  timezone_type: 3
  timezone: UTC

Testing datetime:
****Encrypted default type is compatible with encrypted datetime****
c_det:
  date: 1753-01-01 00:00:00.000000
  timezone_type: 3
  timezone: UTC
c_rand:
  date: 9999-12-31 23:59:59.997000
  timezone_type: 3
  timezone: UTC

Testing datetime2:
****Encrypted default type is compatible with encrypted datetime2****
c_det:
  date: 0001-01-01 00:00:00.000000
  timezone_type: 3
  timezone: UTC
c_rand:
  date: 9999-12-31 23:59:59.1000000
  timezone_type: 3
  timezone: UTC

Testing smalldatetime:
****Encrypted default type is compatible with encrypted smalldatetime****
c_det:
  date: 1900-01-01 00:00:00.000000
  timezone_type: 3
  timezone: UTC
c_rand:
  date: 2079-06-05 23:59:00.000000
  timezone_type: 3
  timezone: UTC

Testing time:
****Encrypted default type is compatible with encrypted time****
c_det:
  date: 2017-09-12 00:00:00.000000
  timezone_type: 3
  timezone: UTC
c_rand:
  date: 2017-09-12 23:59:59.1000000
  timezone_type: 3
  timezone: UTC

Testing datetimeoffset:
****Encrypted default type is compatible with encrypted datetimeoffset****
c_det:
  date: 0001-01-01 00:00:00.000000
  timezone_type: 1
  timezone: -14:00
c_rand:
  date: 9999-12-31 23:59:59.1000000
  timezone_type: 1
  timezone: +14:00