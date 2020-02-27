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
$directions = array(SQLSRV_PARAM_OUT, SQLSRV_PARAM_INOUT);

// this is a list of implicit datatype conversion that SQL Server allows (https://docs.microsoft.com/en-us/sql/t-sql/data-types/data-type-conversion-database-engine)
$compatList = array("char(5)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"),
                    "varchar(max)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"),
                    "nchar(5)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"),
                    "nvarchar(max)" => array( "SQLSRV_SQLTYPE_CHAR(5)", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR(5)", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC", "SQLSRV_SQLTYPE_NTEXT", "SQLSRV_SQLTYPE_TEXT", "SQLSRV_SQLTYPE_XML"));

$conn = AE\connect();

function compareResults($dataType, $sqlType, $c_detOut, $c_randOut, $inputValues)
{
    $success = true;
    if ($c_detOut != $inputValues[0] || $c_randOut != $inputValues[1]) {
        echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType:\n";
        print("    c_det: " . $c_detOut . "\n");
        print("    c_rand: " . $c_randOut . "\n");

        $success = false;
    }
    
    return $success;
}

function testOutputParam($conn, $spname, $direction, $dataType, $sqlType, $inputValues)
{
    // The driver does not support these types as output params, simply return
    if (isDateTimeType($sqlType) || isLOBType($sqlType)) {
        return true;
    }
    
    global $compatList;
    
    $sqlTypeConstant = get_sqlType_constant($sqlType);

    // Call store procedure
    $outSql = AE\getCallProcSqlPlaceholders($spname, 2);

    // Set these to NULL such that the PHP type of each output parameter is inferred
    // from the SQLSRV_SQLTYPE_* constant
    $c_detOut = null;
    $c_randOut = null;
    
    $stmt = sqlsrv_prepare(
        $conn,
        $outSql,
        array(array(&$c_detOut, SQLSRV_PARAM_INOUT, null, $sqlTypeConstant),
        array(&$c_randOut, SQLSRV_PARAM_INOUT, null, $sqlTypeConstant))
    );

    if (!$stmt) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_execute($stmt);

    $success = false;
    $errors = sqlsrv_errors();
    if (AE\IsDataEncrypted()) {
        if (empty($errors)) {
            // With data encrypted, it's a lot stricter, so the results are expected
            // to be comparable
            $success = compareResults($dataType, $sqlType, $c_detOut, $c_randOut, $inputValues);
        } else {
            // This should return 22018, the SQLSTATE for any incompatible conversion,
            // except the XML type
            $success = ($errors[0]['SQLSTATE'] === '22018');
            if (!$success) {
                if ($sqlType === 'SQLSRV_SQLTYPE_XML') {
                    $success = ($errors[0]['SQLSTATE'] === '42000');
                } else {
                    echo "Encrypted data: unexpected errors with SQL type: $sqlType\n";
                }
            }
        }
    } else {
        $compatible = isCompatible($compatList, $dataType, $sqlType);
        if ($compatible && empty($errors)) {
            $success = true;
        } else {
            // Even if $dataType is compatible with $sqlType sometimes
            // we still get errors from the server -- if so, it should
            // return SQLSTATE '42000', indicating an error when
            // converting from one type to another
            // With data NOT encrypted, converting string types to other
            // types will not return '22018'
            $success = ($errors[0]['SQLSTATE'] === '42000');
            if (!$success) {
                echo "Failed with SQL type: $sqlType\n";
            }
        }
    }

    return $success;
}

////////////////////////////////////////////////////////////////////////////////////////

foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType:\n";
    $success = true;

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array(new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);

    // Create a Store Procedure
    $spname = 'selectAllColumns';
    createProc($conn, $spname, "@c_det $dataType OUTPUT, @c_rand $dataType OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");

    // insert a row
    // Take the second and third entres from the various $[$dataType]_params in AEData.inc
    // e.g. with $dataType = 'varchar(max)', use $varchar_params[1] and $varchar_params[2] 
    // to form an array
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);
    if ($r === false) {
        fatalError("Failed to insert data of type $dataType\n");
    }

    foreach ($directions as $direction) {
        $dir = ($direction == SQLSRV_PARAM_OUT) ? 'SQLSRV_PARAM_OUT' : 'SQLSRV_PARAM_INOUT';
        echo "Testing as $dir:\n";

        // test each SQLSRV_SQLTYPE_ constants
        foreach ($sqlTypes as $sqlType) {
            $success = testOutputParam($conn, $spname, $direction, $dataType, $sqlType, $inputValues);
            if (!$success) {
                // No point to continue looping
                echo("Test failed: $dataType as $sqlType\n");
                die(print_r(sqlsrv_errors(), true));
            }
        }
    }
    
    dropProc($conn, $spname);
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
