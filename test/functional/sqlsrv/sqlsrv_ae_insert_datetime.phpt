--TEST--
Test for inserting and retrieving encrypted data of datetime types
--DESCRIPTION--
Bind params using sqlsrv_prepare without any sql_type specified
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

date_default_timezone_set("Canada/Pacific");
$dataTypes = array( "date", "datetime", "datetime2", "smalldatetime", "time", "datetimeoffset" );
$conn = AE\connect();

foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType: \n";

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array( new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);

    // insert a row
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);
    if ($r === false) {
        is_incompatible_types_error($dataType, "default type");
    } else {
        echo "****Encrypted default type is compatible with encrypted $dataType****\n";
        if ($dataType != "time") {
            AE\fetchAll($conn, $tbname);
        } else {
            $sql = "SELECT * FROM $tbname";
            $stmt = sqlsrv_query($conn, $sql);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            foreach ($row as $key => $value) {
                //var_dump( $row );
                $t = $value->format('H:i:s');
                print "$key: $t\n";
            }
        }
    }
    dropTable($conn, $tbname);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--

Testing date: 
****Encrypted default type is compatible with encrypted date****
c_det:
  date: 0001-01-01 00:00:00.000000
  timezone_type: 3
  timezone: Canada/Pacific
c_rand:
  date: 9999-12-31 00:00:00.000000
  timezone_type: 3
  timezone: Canada/Pacific

Testing datetime: 
****Encrypted default type is compatible with encrypted datetime****
c_det:
  date: 1753-01-01 00:00:00.000000
  timezone_type: 3
  timezone: Canada/Pacific
c_rand:
  date: 9999-12-31 23:59:59.997000
  timezone_type: 3
  timezone: Canada/Pacific

Testing datetime2: 
****Encrypted default type is compatible with encrypted datetime2****
c_det:
  date: 0001-01-01 00:00:00.000000
  timezone_type: 3
  timezone: Canada/Pacific
c_rand:
  date: 9999-12-31 23:59:59.123456
  timezone_type: 3
  timezone: Canada/Pacific

Testing smalldatetime: 
****Encrypted default type is compatible with encrypted smalldatetime****
c_det:
  date: 1900-01-01 00:00:00.000000
  timezone_type: 3
  timezone: Canada/Pacific
c_rand:
  date: 2079-06-05 23:59:00.000000
  timezone_type: 3
  timezone: Canada/Pacific

Testing time: 
****Encrypted default type is compatible with encrypted time****
c_det: 00:00:00
c_rand: 23:59:59

Testing datetimeoffset: 
****Encrypted default type is compatible with encrypted datetimeoffset****
c_det:
  date: 0001-01-01 00:00:00.000000
  timezone_type: 1
  timezone: -14:00
c_rand:
  date: 9999-12-31 23:59:59.123456
  timezone_type: 1
  timezone: +14:00
