--TEST--
Test for inserting and retrieving encrypted data of datetime types
Bind params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';

date_default_timezone_set("Canada/Pacific");
$dataTypes = array( "date", "datetime", "datetime2", "smalldatetime", "time", "datetimeoffset" );
$compatList = array( "date" => array( "SQLSRV_SQLTYPE_DATE", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_DATETIME2", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_VARCHAR"),
                     "datetime" => array( "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_DATETIME2", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_VARCHAR"),
                     "datetime2" => array( "SQLSRV_SQLTYPE_DATETIME2", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_VARCHAR"),
                     "smalldatetime" => array( "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_DATETIME2", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_VARCHAR" ), 
                     "time" => array( "SQLSRV_SQLTYPE_DATETIME2", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_VARCHAR" ),
                     "datetimeoffset" => array( "SQLSRV_SQLTYPE_DATETIME2", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_VARCHAR" ));

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
                    if ( $compatType == $sqlType )
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
                if ( "SQLSRV_SQLTYPE_" . strtoupper( $dataType ) == $sqlType )
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
                
                if ( $row["c_det"] != new DateTime( $inputValues[0] ) || $row["c_rand"] != new DateTime( $inputValues[1] ))
                {
                    echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                    var_dump( $inputValues );
                    var_dump( $row );
                    $success = false;
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

Testing date: 
Test successfully done.

Testing datetime: 
Test successfully done.

Testing datetime2: 
Test successfully done.

Testing smalldatetime: 
Test successfully done.

Testing time: 
Test successfully done.

Testing datetimeoffset: 
Test successfully done.