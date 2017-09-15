--TEST--
Test for inserting and retrieving encrypted data of numeric types
Bind params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

$dataTypes = array( "bit", "tinyint", "smallint", "int", "bigint", "decimal(18,5)", "numeric(10,5)", "float", "real" );
$compatList = array( "bit" => array( "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_VARCHAR"),
                     "tinyint" => array( "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_VARCHAR"),
                     "smallint" => array( "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_VARCHAR"),
                     "int" => array( "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_VARCHAR"),
                     "bigint" => array( "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL"),
                     "decimal(18,5)" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_VARCHAR"),
                     "numeric(10,5)" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_VARCHAR"),
                     "float" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_VARCHAR"),
                     "real" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_VARCHAR" ));
$epsilon = 0.0001;

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
        
        if ( !is_col_enc() )
        {
            if ( $r === false  )
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
        }
        else
        {
            if ( $r === false )
            {
                if ( stripos( "SQLSRV_SQLTYPE_" . $dataType, $sqlType ) !== false )
                {
                    echo "$sqlType should be compatible with $dataType\n";
                    $success = false;
                }
            }
            else
            {
                $sql = "SELECT * FROM $tbname";
                $stmt = sqlsrv_query( $conn, $sql );
                $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC );
                
                if ( $dataType == "float" || $dataType == "real" )
                {
                    if ( abs( $row["c_det"] - $inputValues[0] ) > $epsilon || abs( $row["c_rand"] - $inputValues[1] ) > $epsilon )
                    {
                        echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                        $success = false;
                    }
                }
                else
                {
                    if ( $row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1] )
                    {
                        echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                        $success = false;
                    }
                }
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

Testing bit: 
Test successfully done.

Testing tinyint: 
Test successfully done.

Testing smallint: 
Test successfully done.

Testing int: 
Test successfully done.

Testing bigint: 
Test successfully done.

Testing decimal(18,5): 
Test successfully done.

Testing numeric(10,5): 
Test successfully done.

Testing float: 
Test successfully done.

Testing real: 
Test successfully done.