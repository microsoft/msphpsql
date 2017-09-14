--TEST--
Test for inserting and retrieving encrypted data of money types
No PDO::PARAM_ tpe specified when binding parameters
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

$dataTypes = array( "smallmoney", "money" );

try
{
    $conn = ae_connect();
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );

    foreach ( $dataTypes as $dataType ) {
        echo "\nTesting $dataType:\n";
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
                $stmt = $conn->query( $sql );
                $row = $stmt->fetch( PDO::FETCH_ASSOC );
                if ( $row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1] )
                {
                    echo "Incorrect output retrieved for datatype $dataType.\n";
                    $success = false;
                }
            }
        }
        else
        {
            if ( $r === false )
            {
                if ( $stmt->errorInfo()[0] != "22018" )
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
    unset( $stmt );
    unset( $conn );
}
catch( PDOException $e )
{
    echo $e->getMessage();
}
?>
--EXPECT--

Testing smallmoney:
Test successfully done.

Testing money:
Test successfully done.