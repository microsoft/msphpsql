--TEST--
Test for inserting and retrieving encrypted data of string types
--DESCRIPTION--
Bind params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

$dataTypes = array( "char(5)", "varchar(max)", "nchar(5)", "nvarchar(max)" );

// this is a list of implicit datatype conversion that SQL Server allows (https://docs.microsoft.com/en-us/sql/t-sql/data-types/data-type-conversion-database-engine)
$compatList = array( "char(5)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML" ),
                     "varchar(max)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML" ),
                     "nchar(5)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML" ),
                     "nvarchar(max)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML" ));

$conn = AE\connect();

foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType: \n";
    $success = true;

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array( new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);

    // test each SQLSRV_SQLTYPE_ constants
    foreach ($sqlTypes as $sqlType) {
        // insert a row
        $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
        $sqlType = get_default_size_prec($sqlType);
        $inputs = array(new AE\BindParamOption($inputValues[0], null, null, $sqlType), new AE\BindParamOption($inputValues[1], null, null, $sqlType));
        $r;
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputs[0], $colMetaArr[1]->colName => $inputs[1] ), $r, AE\INSERT_PREPARE_PARAMS);

        if ($r === false) {
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
                    echo "$sqlType should be compatible with $dataType\n";
                    $success = false;
                }
            }
        } else {
            $sql = "SELECT * FROM $tbname";
            $stmt = sqlsrv_query($conn, $sql);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1]) {
                echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                $success = false;
            }
        }
        sqlsrv_query($conn, "TRUNCATE TABLE $tbname");
    }
    if ($success) {
        echo "Test successfully done.\n";
    }
    dropTable($conn, $tbname);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
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
