--TEST--
Test to incorrectly bind input parameters as output parameters of various types
--DESCRIPTION--
Similar to pdo_ae_output_param_errors.phpt - test to incorrectly bind input parameters 
as output parameters of various types. The key is to enable ColumnEncryption and 
check for memory leaks.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
function checkODBCVersion($conn)
{
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    $vers = explode(".", $msodbcsql_ver);

    if ($vers[0] >= 17 && $vers[1] > 0){
        return true;
    } else {
        return false;
    }
}

// Check if the ODBC driver supports connecting with ColumnEncryption
// If not simply return
require_once('MsHelper.inc');
require_once('MsSetup.inc');

$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn === false) {
    fatalError("Failed to connect to $server.");
}
if (!checkODBCVersion($conn)) {
    echo "Done\n";
    sqlsrv_close($conn);
    return;
}

// Create a dummy table with various data types
$tbname = 'srv_output_param_errors';
$colMetaArr = array( new AE\ColumnMeta("int", "c1_int"),
                     new AE\ColumnMeta("smallint", "c2_smallint"),
                     new AE\ColumnMeta("tinyint", "c3_tinyint"),
                     new AE\ColumnMeta("bit", "c4_bit"),
                     new AE\ColumnMeta("bigint", "c5_bigint"),
                     new AE\ColumnMeta("decimal(18,5)", "c6_decimal"),
                     new AE\ColumnMeta("numeric(10,5)", "c7_numeric"),
                     new AE\ColumnMeta("float", "c8_float"),
                     new AE\ColumnMeta("real", "c9_real"),
                     new AE\ColumnMeta("date", "c10_date"),
                     new AE\ColumnMeta("datetime", "c11_datetime"),
                     new AE\ColumnMeta("datetime2", "c12_datetime2"),
                     new AE\ColumnMeta("datetimeoffset", "c13_datetimeoffset"),
                     new AE\ColumnMeta("time", "c14_time"),
                     new AE\ColumnMeta("char(5)", "c15_char"),
                     new AE\ColumnMeta("varchar(max)", "c16_varchar"),
                     new AE\ColumnMeta("nchar(5)", "c17_nchar"),
                     new AE\ColumnMeta("nvarchar(max)", "c18_nvarchar"));
AE\createTable($conn, $tbname, $colMetaArr);

// Create a dummy select statement
$sql = "SELECT * FROM $tbname WHERE c1_int = ? OR c2_smallint = ? OR c3_tinyint = ? ";
$sql .= "OR c4_bit = ? OR c5_bigint = ? OR c6_decimal = ? OR c7_numeric = ? OR c8_float = ? ";
$sql .= "OR c9_real = ? OR c10_date = ? OR c11_datetime = ? OR c12_datetime2 = ? ";
$sql .= "OR c13_datetimeoffset = ? OR c14_time = ? OR c15_char = ? ";
$sql .= "OR c16_varchar = ? OR c17_nchar = ? OR c18_nvarchar = ?";

$options = array_merge($connectionOptions, 
                        array('ColumnEncryption' => 'Enabled', 
                              'CharacterSet' => 'UTF-8'));

// Initialize all inputs, set bigint, decimal and numeric as empty strings
$intOut = 0;
$smallintOut = 0;
$tinyintOut = 0;
$bitOut = 0;
$bigintOut = '';
$decimalOut = '';
$numericOut = '';
$floatOut = 0.0;
$realOut = 0.0;
$dateOut = '';
$datetimeOut = '';
$datetime2Out = '';
$datetimeoffsetOut = '';
$timeOut = '';
$charOut = '';
$varcharOut = '';
$ncharOut = '';
$nvarcharOut = '';

$usage1 = 0;
$rounds = 30;
for ($i = 0; $i < $rounds; $i++) {
    // Connect with ColumnEncryption enabled
    $conn2 = sqlsrv_connect($server, $options);
    if ($conn2 === false) {
        fatalError("Failed to connect to $server.");
    }
    
    $stmt = sqlsrv_prepare($conn2, $sql, array( array( &$intOut, SQLSRV_PARAM_OUT ),
                                         array( &$smallintOut, SQLSRV_PARAM_OUT ),
                                         array( &$tinyintOut, SQLSRV_PARAM_OUT ),
                                         array( &$bitOut, SQLSRV_PARAM_OUT ),
                                         array( &$bigintOut, SQLSRV_PARAM_OUT ),
                                         array( &$decimalOut, SQLSRV_PARAM_OUT ),
                                         array( &$numericOut, SQLSRV_PARAM_OUT ),
                                         array( &$floatOut, SQLSRV_PARAM_OUT ),
                                         array( &$realOut, SQLSRV_PARAM_OUT ),
                                         array( &$dateOut, SQLSRV_PARAM_OUT ),
                                         array( &$datetimeOut, SQLSRV_PARAM_OUT ),
                                         array( &$datetime2Out, SQLSRV_PARAM_OUT ),
                                         array( &$datetimeoffsetOut, SQLSRV_PARAM_OUT ),
                                         array( &$timeOut, SQLSRV_PARAM_OUT ),
                                         array( &$charOut, SQLSRV_PARAM_OUT ),
                                         array( &$varcharOut, SQLSRV_PARAM_OUT ),
                                         array( &$ncharOut, SQLSRV_PARAM_OUT ),
                                         array( &$nvarcharOut, SQLSRV_PARAM_OUT )));
    
    // Expect the following to fail so just ignore the errors
    sqlsrv_execute($stmt);

    // Compare the current memory usage to the previous usage
    if ($i == 0) {
        $usage1 = memory_get_usage();
    } else {
        $usage2 = memory_get_usage();
        if ($usage2 > $usage1) {
            echo "Memory leaks ($i)! Expected $usage1 but now $usage2\n";
        }
    }

    // Free the resources to trigger the destruction of any zvals with refcount of 0
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn2);
}

sqlsrv_query($conn, "DROP TABLE $tbname");
sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done