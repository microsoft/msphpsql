--TEST--
Test for inserting and retrieving encrypted data of datetime types
--DESCRIPTION--
Bind output params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

date_default_timezone_set("Canada/Pacific");
$dataTypes = array( "date", "datetime", "datetime2", "smalldatetime", "time", "datetimeoffset" );

// this is a list of implicit datatype conversion that SQL Server allows (https://docs.microsoft.com/en-us/sql/t-sql/data-types/data-type-conversion-database-engine)
$compatList = array( "date" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DATE", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_DATETIME2" ),
                     "datetime" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DATE", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_DATETIME2" ),
                     "datetime2" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DATE", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_DATETIME2" ),
                     "smalldatetime" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DATE", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_DATETIME2" ),
                     "time" => array( "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_TIME", "SQLSRV_SQLTYPE_DATETIMEOFFSET", "SQLSRV_SQLTYPE_DATETIME2" ),
                     "datetimeoffset" => array("SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIMEOFFSET") );

$conn = AE\connect();
	
foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType: \n";
    $success = true;

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
    }
	
    // Create a Store Procedure
    $spname = 'selectAllColumns';
    $spSql = "CREATE PROCEDURE $spname (@c_det $dataType OUTPUT, @c_rand $dataType OUTPUT ) AS SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
    sqlsrv_query($conn, $spSql);

    // test each SQLSRV_SQLTYPE_ constants
    foreach ($sqlTypes as $sqlType) {
        if (!AE\isColEncrypted()) {
            $isCompatible = false;
            foreach ($compatList[$dataType] as $compatType) {
                if (stripos($compatType, $sqlType) !== false) {
                    $isCompatible = true;
                }
            }
            // 22018 is the SQLSTATE for any incompatible conversion errors
            if ($isCompatible && sqlsrv_errors()[0]['SQLSTATE'] == 22018) {
                echo "$sqlType should be compatible with $dataType\n";
                $success = false;
            }
        } else {
            // always encrypted only allow sqlType that is identical to the encrypted column datatype
            if (stripos("SQLSRV_SQLTYPE_" . $dataType, $sqlType) !== false) {
                $sqlTypeConstant = get_sqlType_constant($sqlType);
    	        // Call store procedure
                $outSql = AE\getCallProcSqlPlaceholders($spname, 2);
                $c_detOut = '';
                $c_randOut = '';
                $stmt = sqlsrv_prepare( $conn, $outSql, 
                    array( array( &$c_detOut, SQLSRV_PARAM_OUT, null, $sqlTypeConstant ),
                    array( &$c_randOut, SQLSRV_PARAM_OUT, null, $sqlTypeConstant )));
                if (!$stmt) {
                    die(print_r(sqlsrv_errors(), true));
                }							
                sqlsrv_execute($stmt);
				$errors = sqlsrv_errors();
				if ( empty($errors) ) {
					// SQLSRV_PHPTYPE_DATETIME not supported
		            echo "$dataType should not be compatible with any datetime type.\n";
                    $success = false;
				}

                sqlsrv_query($conn, "TRUNCATE TABLE $tbname");				
            }
		}		
    }
	
    if ($success) {
        echo "Test successfully done.\n";
    }
	dropProc($conn, $spname);
    dropTable($conn, $tbname);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
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
