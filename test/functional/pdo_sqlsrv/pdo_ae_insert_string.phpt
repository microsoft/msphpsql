--TEST--
Test for inserting and retrieving encrypted data of string types
No PDO::PARAM_ type specified when binding parameters
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

$dataTypes = array( "char(5)", "varchar(max)", "nchar(5)", "nvarchar(max)" );

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

Testing char(5):
****Encrypted default type is compatible with encrypted char(5)****
c_det: -leng
c_rand: th, n

Testing varchar(max):
****Encrypted default type is compatible with encrypted varchar(max)****
c_det: Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.
c_rand: Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.

Testing nchar(5):
****Encrypted default type is compatible with encrypted nchar(5)****
c_det: -leng
c_rand: th Un

Testing nvarchar(max):
****Encrypted default type is compatible with encrypted nvarchar(max)****
c_det: When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).
c_rand: Otherwise, the implicit conversion will result in a Unicode large-value (max).