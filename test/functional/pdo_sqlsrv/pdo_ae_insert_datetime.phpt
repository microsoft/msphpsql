--TEST--
Test for inserting and retrieving encrypted data of datetime types
No PDO::PARAM_ type specified when binding parameters
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
        
        // insert a row
        $inputValues = array_slice( ${explode( "(", $dataType )[0] . "_params"}, 1, 2 );
        $r;
        $stmt = insert_row( $conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r );
        if ( $r === false ) {
            is_incompatible_types_error( $stmt, $dataType, "default type" );
        }
        else {
            echo "****Encrypted default type is compatible with encrypted $dataType****\n";
            fetch_all( $conn, $tbname );
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
****Encrypted default type is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31

Testing datetime:
****Encrypted default type is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997

Testing datetime2:
****Encrypted default type is compatible with encrypted datetime2****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999

Testing smalldatetime:
****Encrypted default type is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00

Testing time:
****Encrypted default type is compatible with encrypted time****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999

Testing datetimeoffset:
****Encrypted default type is compatible with encrypted datetimeoffset****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00