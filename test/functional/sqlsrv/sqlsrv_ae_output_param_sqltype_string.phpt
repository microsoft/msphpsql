--TEST--
Test for inserting and retrieving encrypted data of string types
--DESCRIPTION--
Bind output params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

$dataTypes = array("char(5)", "varchar(max)", "nchar(5)", "nvarchar(max)");
$directions = array("SQLSRV_PARAM_OUT", "SQLSRV_PARAM_INOUT");

// this is a list of implicit datatype conversion that SQL Server allows (https://docs.microsoft.com/en-us/sql/t-sql/data-types/data-type-conversion-database-engine)
$compatList = array("char(5)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"),
                    "varchar(max)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"),
                    "nchar(5)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"),
                    "nvarchar(max)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"));

$conn = AE\connect();
	
foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType:\n";
    $success = true;

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array(new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);

    // TODO: It's a good idea to test conversions between different datatypes when AE is off as well. 
    if (AE\isColEncrypted()) {
        // Create a Store Procedure
        $spname = 'selectAllColumns';
        createProc($conn, $spname, "@c_det $dataType OUTPUT, @c_rand $dataType OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");
    }
	
    // insert a row
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);
    if ($r === false) {
        is_incompatible_types_error($dataType, "default type");
    }
	
    foreach($directions as $direction) {
        echo "Testing as $direction:\n";

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
                // skip unsupported datetime types
                if (!isDateTimeType($sqlType)) {
                    $sqlTypeConstant = get_sqlType_constant($sqlType);

                    // Call store procedure
                    $outSql = AE\getCallProcSqlPlaceholders($spname, 2);
                    $c_detOut = '';
                    $c_randOut = '';
                    $stmt = sqlsrv_prepare($conn, $outSql, 
                        array(array(&$c_detOut, SQLSRV_PARAM_INOUT, null, $sqlTypeConstant),
                        array(&$c_randOut, SQLSRV_PARAM_INOUT, null, $sqlTypeConstant)));

                    if (!$stmt) {
                        die(print_r(sqlsrv_errors(), true));
                    }						
                        
                    sqlsrv_execute($stmt);
                    $errors = sqlsrv_errors();
                    
                    if (!empty($errors) ) {
                        if (stripos("SQLSRV_SQLTYPE_" . $dataType, $sqlType) !== false) {
                            var_dump(sqlsrv_errors());
                            $success = false;                    
                        }                
                    }
                    else
                    {
                        if (AE\IsDataEncrypted() || stripos("SQLSRV_SQLTYPE_" . $dataType, $sqlType) !== false) {                        
                            if ($c_detOut != $inputValues[0] || $c_randOut != $inputValues[1]) {
                                echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType:\n";
                                print("    c_det: " . $c_detOut . "\n");
                                print("    c_rand: " . $c_randOut . "\n");                          
                                $success = false;
                            }
                        }
                    }
                    
                    sqlsrv_free_stmt($stmt);
                }
            }            
        }
    }
    
    if (AE\isColEncrypted()) {
        dropProc($conn, $spname);
    }
    if ($success) {
        echo "Test successfully done.\n";
    }
    dropTable($conn, $tbname);
}

sqlsrv_close($conn);
?>
--EXPECT--

Testing char(5):
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing nchar(5):
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing nvarchar(max):
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.
