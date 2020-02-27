--TEST--
Test for inserting and retrieving encrypted data of numeric types
--DESCRIPTION--
Bind output params using sqlsrv_prepare with all sql_type
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

$dataTypes = array("bit", "tinyint", "smallint", "int", "bigint", "decimal(18,5)", "numeric(10,5)", "float", "real" );
$directions = array(SQLSRV_PARAM_OUT, SQLSRV_PARAM_INOUT);

// this is a list of implicit datatype conversion that SQL Server allows (https://docs.microsoft.com/en-us/sql/t-sql/data-types/data-type-conversion-database-engine)
$compatList = array("bit" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP"),
                    "tinyint" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP"),
                    "smallint" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP"),
                    "int" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP"),
                    "bigint" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP" ),
                    "decimal(18,5)" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP"),
                    "numeric(10,5)" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TIMESTAMP"),
                    "float" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT"),
                    "real" => array( "SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_DATETIME", "SQLSRV_SQLTYPE_SMALLDATETIME", "SQLSRV_SQLTYPE_DECIMAL(18,5)", "SQLSRV_SQLTYPE_NUMERIC(10,5)", "SQLSRV_SQLTYPE_FLOAT", "SQLSRV_SQLTYPE_REAL", "SQLSRV_SQLTYPE_BIGINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_MONEY", "SQLSRV_SQLTYPE_SMALLMONEY", "SQLSRV_SQLTYPE_BIT"));

function compareResults($dataType, $sqlType, $c_detOut, $c_randOut, $inputValues)
{
    $epsilon = 0.0001;
    $success = true;
    
    if ($dataType == "float" || $dataType == "real") {
        if (abs($c_detOut - $inputValues[0]) > $epsilon || abs($c_randOut - $inputValues[1]) > $epsilon) {
            echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType:\n";
            print("    c_det: " . $c_detOut . "\n");
            print("    c_rand: " . $c_randOut . "\n");
            $success = false;
        }
    } else {
        if ($c_detOut != $inputValues[0] || $c_randOut != $inputValues[1]) {
            echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType:\n";
            print("    c_det: " . $c_detOut . "\n");
            print("    c_rand: " . $c_randOut . "\n");
            $success = false;
        }
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
        array(array( &$c_detOut, $direction, null, $sqlTypeConstant),
        array(&$c_randOut, $direction, null, $sqlTypeConstant ))
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
            // to be numeric and comparable
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
            // we still get errors from the server -- if so, it might
            // return either SQLSTATE '42000' or '22018' (operand type 
            // clash but only happens with some certain types)
            // E.g. when converting a bigint to int or an int to numeric, 
            // SQLSTATE '42000' is returned, indicating an error when 
            // converting from one type to another.
            // TODO 11559: investigate if SQLSTATE '42000' is indeed acceptable
            $success = ($errors[0]['SQLSTATE'] === '42000' || ($errors[0]['SQLSTATE'] === '22018' && in_array($sqlType, ['SQLSRV_SQLTYPE_XML', 'SQLSRV_SQLTYPE_BINARY', 'SQLSRV_SQLTYPE_VARBINARY', 'SQLSRV_SQLTYPE_UNIQUEIDENTIFIER', 'SQLSRV_SQLTYPE_TIMESTAMP'])));
            if (!$success) {
                if ($compatible) {
                    echo "$dataType should be compatible with $sqlType.\n";
                } else {
                    echo "Failed with SQL type: $sqlType\n";
                }
            }
        }
    }
    
    return $success;
}

////////////////////////////////////////////////////////////////////////////////////////

$conn = AE\connect();

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
    // Take the second and third entres (some edge cases) from the various 
    // $[$dataType]_params in AEData.inc
    // e.g. with $dataType = 'decimal(18,5)', use $decimal_params[1] and $decimal_params[2] 
    // to form an array, namely [-9223372036854.80000, 9223372036854.80000]
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    // convert input values to strings for decimals and numerics
    if ($dataTypes == "decimal(18,5)" || $dataTypes == "numeric(10,5)") {
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => (string) $inputValues[0], $colMetaArr[1]->colName => (string) $inputValues[1] ), $r);
    } else {
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);
    }
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

Testing bit:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing tinyint:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing smallint:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing int:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing bigint:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing decimal(18,5):
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing numeric(10,5):
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing float:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.

Testing real:
Testing as SQLSRV_PARAM_OUT:
Testing as SQLSRV_PARAM_INOUT:
Test successfully done.
