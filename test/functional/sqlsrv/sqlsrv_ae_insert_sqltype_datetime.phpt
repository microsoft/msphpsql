--TEST--
Test for inserting and retrieving encrypted data of datetime types
--DESCRIPTION--
Bind params using sqlsrv_prepare with all sql_type
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

    // test each SQLSRV_SQLTYPE_ constants
    foreach ($sqlTypes as $sqlType) {
        // insert a row
        $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
        $sqlType = get_default_size_prec($sqlType);
        $inputs = array(new AE\BindParamOption($inputValues[0], null, null, $sqlType), new AE\BindParamOption($inputValues[1], null, null, $sqlType));
        $r;
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputs[0], $colMetaArr[1]->colName => $inputs[1] ), $r, AE\INSERT_PREPARE_PARAMS);

        if (!AE\isDataEncrypted()) {
            if ($r === false) {
                $isCompatible = false;
                foreach ($compatList[$dataType] as $compatType) {
                    if ($compatType == $sqlType) {
                        $isCompatible = true;
                    }
                }
                // 22018 is the SQLSTATE for any incompatible conversion errors
                if ($isCompatible && sqlsrv_errors()[0]['SQLSTATE'] == 22018) {
                    echo "$sqlType should be compatible with $dataType\n";
                    var_dump(sqlsrv_errors());
                    $success = false;
                }
            }
        } else {
            if ($r === false) {
                // always encrypted only allow sqlType that is identical to the encrypted column datatype
                if ("SQLSRV_SQLTYPE_" . strtoupper($dataType) == $sqlType) {
                    echo "$sqlType should be compatible with $dataType\n";
                    var_dump(sqlsrv_errors());
                    $success = false;
                }
            } else {
                $sql = "SELECT * FROM $tbname";
                $stmt = sqlsrv_query($conn, $sql);
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if ($row["c_det"] != new DateTime($inputValues[0]) || $row["c_rand"] != new DateTime($inputValues[1])) {
                    echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                    var_dump($inputValues);
                    var_dump($row);
                    $success = false;
                }
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
