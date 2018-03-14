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
$epsilon = 0.0001;

$conn = AE\connect();
	
foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType: \n";
    $success = true;

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array(new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);
	
    if (AE\isColEncrypted()) {
		// Create a Store Procedure
		$spname = 'selectAllColumns';
		createProc($conn, $spname, "@c_det $dataType OUTPUT, @c_rand $dataType OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");
	}
	
    // insert a row
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    // convert input values to strings for decimals and numerics
    if ($dataTypes == "decimal(18,5)" || $dataTypes == "numeric(10,5)") {
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => (string) $inputValues[0], $colMetaArr[1]->colName => (string) $inputValues[1] ), $r);
    } else {
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);
    }
    if ($r === false) {
        is_incompatible_types_error($dataType, "default type");
    }

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
			$sqlTypeConstant = get_sqlType_constant($sqlType);

			// Call store procedure
			$outSql = AE\getCallProcSqlPlaceholders($spname, 2);
			$c_detOut = '';
			$c_randOut = '';
			$stmt = sqlsrv_prepare($conn, $outSql, 
				array(array( &$c_detOut, SQLSRV_PARAM_OUT, null, $sqlTypeConstant),
				array(&$c_randOut, SQLSRV_PARAM_OUT, null, $sqlTypeConstant)));
			if (!$stmt) {
				die(print_r(sqlsrv_errors(), true));
			}							
			sqlsrv_execute($stmt);
			$errors = sqlsrv_errors();
			
			if (!empty($errors)) {
                if (stripos("SQLSRV_SQLTYPE_" . $dataType, $sqlType) !== false) {
					var_dump( sqlsrv_errors() );
                    $success = false;               
				}					
			}
			else {
				print("c_det: " . $c_detOut . "\n");
				print("c_rand: " . $c_randOut . "\n");

				if ($dataType == "float" || $dataType == "real") {
					if (abs($c_detOut - $inputValues[0]) > $epsilon || abs($c_randOut - $inputValues[1]) > $epsilon) {
						echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
						$success = false;
					}
				} else {
					if ($c_detOut != $inputValues[0] || $c_randOut != $inputValues[1]) {
						echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
						$success = false;
					}
				}				
			}
		}		
    }
	
    if (!AE\isColEncrypted()) {
        AE\fetchAll($conn, $tbname);
	}
	else {
		dropProc($conn, $spname);	
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

Testing bit: 
c_det: 1
c_rand: 0
Test successfully done.

Testing tinyint: 
c_det: 0
c_rand: 255
Test successfully done.

Testing smallint: 
c_det: -32767
c_rand: 32767
Test successfully done.

Testing int: 
c_det: -2147483647
c_rand: 2147483647
Test successfully done.

Testing bigint: 
c_det: -922337203685479936
c_rand: 922337203685479936
Test successfully done.

Testing decimal(18,5): 
c_det: -9223372036854.80000
c_rand: 9223372036854.80000
Test successfully done.

Testing numeric(10,5): 
c_det: -21474.83647
c_rand: 21474.83647
Test successfully done.

Testing float: 
c_det: -9223372036.8548
c_rand: 9223372036.8548
Test successfully done.

Testing real: 
c_det: -2147.4829101562
c_rand: 2147.4829101562
Test successfully done.
