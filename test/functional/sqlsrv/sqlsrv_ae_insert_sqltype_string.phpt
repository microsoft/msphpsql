--TEST--
Test for inserting and retrieving encrypted data of string types
Bind params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

$dataTypes = array( "char(5)", "varchar(max)", "nchar(5)", "nvarchar(max)" );
$compatList = array( "char(5)" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_VARCHAR"),
                     "varchar(max)" => array( "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_VARCHAR"),
                     "nchar(5)" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_VARCHAR"),
                     "nvarchar(max)" => array( "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_VARCHAR" ));
                     
$conn = ae_connect();

foreach ( $dataTypes as $dataType ) {
    echo "\nTesting $dataType: \n";
    $success = true;
    
    // create table
    $tbname = GetTempTableName( "", false );
    $colMetaArr = array( new columnMeta( $dataType, "c_det" ), new columnMeta( $dataType, "c_rand", null, "randomized" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // test each SQLSRV_SQLTYPE_ constants
    foreach ( $sqlTypes as $sqlType )
    {
        // insert a row
        $inputValues = array_slice( ${explode( "(", $dataType )[0] . "_params"}, 1, 2 );
        $sqlType = get_default_size_prec( $sqlType );
        $paramOp = array( new bindParamOption( 1, null, null, $sqlType ), new bindParamOption( 2, null, null, $sqlType ));
        $r;
        $stmt = insert_row( $conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r, "prepareParamsOp", $paramOp );
        
        if ( $r === false )
        {
            if ( !is_col_enc() )
            {
                $isCompat = false;
                foreach ( $compatList[$dataType] as $compatType )
                {
                    if ( stripos( $compatType, $sqlType ) !== false )
                        $isCompat = true;
                }
                if ( $isCompat )
                {
                    echo "$sqlType should be compatible with $dataType\n";
                    $success = false;
                }
            }
            else
            {
                if ( stripos( "SQLSRV_SQLTYPE_" . $dataType, $sqlType ) !== false )
                {
                    echo "$sqlType should be compatible with $dataType\n";
                    $success = false;
                }
            }
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
        sqlsrv_query( $conn, "TRUNCATE TABLE $tbname" );
    }
    if ( $success )
        echo "Test successfully done.\n";
    DropTable( $conn, $tbname );
}
sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--

Testing char(5): 
Test successfully done.

Testing varchar(max): 
Test successfully done.

Testing nchar(5): 
Test successfully done.

Testing nvarchar(max): 
Test successfully done.