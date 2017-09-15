--TEST--
Test for inserting and retrieving encrypted data of datetime types
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

$dataTypes = array( "date", "datetime", "datetime2", "smalldatetime", "time", "datetimeoffset" );

try
{
    $conn = ae_connect();

    foreach ( $dataTypes as $dataType ) {
        echo "\nTesting $dataType:\n";
        
        // create table
        $tbname = GetTempTableName( "", false );
        $colMetaArr = array( new columnMeta( $dataType, "c_det" ), new columnMeta( $dataType, "c_rand", null, "randomized" ));
        create_table( $conn, $tbname, $colMetaArr );
        
        // prepare statement for inserting into table
        foreach ( $pdoParamTypes as $pdoParamType ) {
            // insert a row
            $inputValues = array_slice( ${explode( "(", $dataType )[0] . "_params"}, 1, 2 );
            $r;
            $stmt = insert_row( $conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r, "prepareBindParam", array( new bindParamOption( 1, $pdoParamType ), new bindParamOption( 2, $pdoParamType )));
            if ( $r === false )
            {
                is_incompatible_types_error( $stmt, $dataType, $pdoParamType );
            }
            else {
                echo "****PDO param type $pdoParamType is compatible with encrypted $dataType****\n";
                fetch_all( $conn, $tbname );
            }
            $conn->query( "TRUNCATE TABLE $tbname" );
        }
        DropTable( $conn, $tbname );
    }
    unset( $stmt );
    unset( $conn );
}
catch( PDOException $e )
{
    echo $e->getMessage();
}
?>
--EXPECT--

Testing date:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31
****PDO param type PDO::PARAM_NULL is compatible with encrypted date****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31
****PDO param type PDO::PARAM_STR is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31
****PDO param type PDO::PARAM_LOB is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31

Testing datetime:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997
****PDO param type PDO::PARAM_NULL is compatible with encrypted datetime****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997

Testing datetime2:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted datetime2****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999
****PDO param type PDO::PARAM_NULL is compatible with encrypted datetime2****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted datetime2****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime2****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime2****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999

Testing smalldatetime:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00
****PDO param type PDO::PARAM_NULL is compatible with encrypted smalldatetime****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00
****PDO param type PDO::PARAM_STR is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00

Testing time:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted time****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999
****PDO param type PDO::PARAM_NULL is compatible with encrypted time****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted time****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999
****PDO param type PDO::PARAM_STR is compatible with encrypted time****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999
****PDO param type PDO::PARAM_LOB is compatible with encrypted time****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999

Testing datetimeoffset:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted datetimeoffset****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00
****PDO param type PDO::PARAM_NULL is compatible with encrypted datetimeoffset****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted datetimeoffset****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00
****PDO param type PDO::PARAM_STR is compatible with encrypted datetimeoffset****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetimeoffset****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00