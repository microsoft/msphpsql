--TEST--
Test to incorrectly bind input parameters as output parameters of various types
--DESCRIPTION--
Test to incorrectly bind input parameters as output parameters of various types. 
The key is to enable ColumnEncryption and check for memory leaks.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

function checkODBCVersion()
{
    $conn = connect();
    $msodbcsql_ver = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
    $vers = explode(".", $msodbcsql_ver);

    unset($conn);
    if ($vers[0] >= 17 && $vers[1] > 0){
        return true;
    } else {
        return false;
    }
}

require_once("MsCommon_mid-refactor.inc");

try {
    // Check if the ODBC driver supports connecting with ColumnEncryption
    // If not simply return
    if (!checkODBCVersion()) {
        echo "Done\n";
        return;
    }

    $conn = connect("ColumnEncryption=Enabled;");

    // Create a dummy table with various data types
    $tbname = 'pdo_output_param_errors';
    $colMetaArr = array("c1_int" => "int",
                        "c2_smallint" => "smallint",
                        "c3_tinyint" => "tinyint",
                        "c4_bit" => "bit",
                        "c5_bigint" => "bigint",
                        "c6_decimal" => "decimal(18,5)",
                        "c7_numeric" => "numeric(10,5)",
                        "c8_float" => "float",
                        "c9_real" => "real",
                        "c10_date" => "date",
                        "c11_datetime" => "datetime",
                        "c12_datetime2" => "datetime2",
                        "c13_datetimeoffset" => "datetimeoffset",
                        "c14_time" => "time",
                        "c15_char" => "char(5)",
                        "c16_varchar" => "varchar(max)",
                        "c17_nchar" => "nchar(5)",
                        "c18_nvarchar" => "nvarchar(max)");
    createTable($conn, $tbname, $colMetaArr);

    // Create a dummy select statement
    $tsql = "SELECT * FROM $tbname WHERE c1_int = ? OR c2_smallint = ? OR c3_tinyint = ? ";
    $tsql .= "OR c4_bit = ? OR c5_bigint = ? OR c6_decimal = ? OR c7_numeric = ? OR c8_float = ? ";
    $tsql .= "OR c9_real = ? OR c10_date = ? OR c11_datetime = ? OR c12_datetime2 = ? ";
    $tsql .= "OR c13_datetimeoffset = ? OR c14_time = ? OR c15_char = ? ";
    $tsql .= "OR c16_varchar = ? OR c17_nchar = ? OR c18_nvarchar = ?";
    
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
    $dateOut = '0001-01-01';
    $datetimeOut = '1753-01-01 00:00:00';
    $datetime2Out = '0001-01-01 00:00:00';
    $datetimeoffsetOut = '1900-01-01 00:00:00 +01:00';
    $timeOut = '00:00:00';
    $charOut = '';
    $varcharOut = '';
    $ncharOut = '';
    $nvarcharOut = '';

    $usage1 = 0;
    $rounds = 30;
    for ($i = 0; $i < $rounds; $i++) {
        $stmt = $conn->prepare($tsql);

        $stmt->bindParam(1, $intOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
        $stmt->bindParam(2, $smallintOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
        $stmt->bindParam(3, $tinyintOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
        $stmt->bindParam(4, $bitOut, PDO::PARAM_INT, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
        $stmt->bindParam(5, $bigintOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(6, $decimalOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(7, $numericOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(8, $floatOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(9, $realOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(10, $dateOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(11, $datetimeOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(12, $datetime2Out, PDO::PARAM_STR, 2048);
        $stmt->bindParam(13, $datetimeoffsetOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(14, $timeOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(15, $charOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(16, $varcharOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(17, $ncharOut, PDO::PARAM_STR, 2048);
        $stmt->bindParam(18, $nvarcharOut, PDO::PARAM_STR, 2048, PDO::SQLSRV_ENCODING_UTF8);

        // Expect the following to fail so just ignore the exception caught
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            ;
        }
        unset($stmt);

        // Compare the current memory usage to the previous usage
        if ($i == 0) {
            $usage1 = memory_get_usage();
        } else {
            $usage2 = memory_get_usage();
            if ($usage2 > $usage1) {
                echo "Memory leaks ($i)! Expected $usage1 but now $usage2\n";
            }
        }
    }

    dropTable($conn, $tbname);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
}

echo "Done\n";
?>
--EXPECT--
Done