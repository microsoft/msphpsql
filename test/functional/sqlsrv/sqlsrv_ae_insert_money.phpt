--TEST--
Test for inserting and retrieving encrypted data of money types
Bind params using sqlsrv_prepare without any sql_type specified
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

$dataTypes = array( "smallmoney", "money" );
$conn = ae_connect();

foreach ( $dataTypes as $dataType ) {
    echo "\nTesting $dataType: \n";
    $success = true;
    
    // create table
    $tbname = GetTempTableName( "", false );
    $colMetaArr = array( new columnMeta( $dataType, "c_det" ), new columnMeta( $dataType, "c_rand", null, "randomized" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // insert a row
    $inputValues = array_slice( ${explode( "(", $dataType )[0] . "_params"}, 1, 2 );
    $r;
    $stmt = insert_row( $conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r );
    
    if ( !is_col_enc() )
    {
        if ( $r === false )
        {
            echo "Default type should be compatible with $dataType.\n";
            $success = false;
        }
        else
        {
            $sql = "SELECT * FROM $tbname";
            $stmt = sqlsrv_query( $conn, $sql );
            $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC );
            if ( $row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1] )
            {
                echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                $success = false;
            }
        }
    }
    else
    {
        if ( $r === false )
        {
            if ( sqlsrv_errors()[0]['SQLSTATE'] != 22018 )
            {
                echo "Incorrect error returned.\n";
                $success = false;
            }
        }
        else
        {
            echo "$dataType is not compatible with any type.\n";
            $success = false;
        }
    }
    if ( $success )
        echo "Test successfully done.\n";
    DropTable( $conn, $tbname );
}
sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--

Testing smallmoney: 
Test successfully done.

Testing money: 
Test successfully done.