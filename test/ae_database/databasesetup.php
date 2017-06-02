<?php
include "AEData.inc";

sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );  

$databaseName = 'AEDemo';
$uid = 'yourUsername';
$pwd = 'yourPassword';
$server = 'yourServer';

$connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd);

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Failed to connect.\n";
    die( print_r(sqlsrv_errors(), true ));
}

// create table for exact numerics
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('dbo.test_AE_exnum', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE_exnum]");
if ($stmt === false) {
    echo "Failed to drop table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_create = "CREATE TABLE dbo.test_AE_exnum([normBigint] [bigint], [encDetBigint] [bigint], [encRandBigint] [bigint],
                                                       [normInt] [int], [encDetInt] [int], [encRandInt] [int],
                                                       [normSmallint] [smallint], [encDetSmallint] [smallint], [encRandSmallint] [smallint],
                                                       [normTinyint] [tinyint], [encDetTinyint] [tinyint], [encRandTinyint] [tinyint],
                                                       [normDecimal] [decimal](18,5), [encDetDecimal] [decimal](18,5), [encRandDecimal] [decimal](18,5),
                                                       [normNumeric] [numeric](18,5), [encDetNumeric] [numeric](18,5), [encRandNumeric] [numeric](18,5),
                                                       [normMoney] [money], [encDetMoney] [money], [encRandMoney] [money],
                                                       [normSmallmoney] [smallmoney], [encDetSmallmoney] [smallmoney], [encRandSmallmoney] [smallmoney],
                                                       [normBit] [bit], [encDetBit] [bit], [encRandBit] [bit])";

$stmt = sqlsrv_query($conn, $sql_create);
if ($stmt === false) {
    echo "Failed to create table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_insert = "INSERT INTO dbo.test_AE_exnum VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params1 = array_merge(array_slice($bigint_params, 0, 3), 
                       array_slice($int_params, 0, 3), 
                       array_slice($smallint_params, 0, 3), 
                       array_slice($tinyint_params, 0, 3), 
                       array_slice($decimal_params, 0, 3), 
                       array_slice($numeric_params, 0, 3), 
                       array_slice($money_params, 0, 3), 
                       array_slice($smallmoney_params, 0, 3), 
                       array_slice($bit_params, 0, 3));
                       
$params2 = array_merge(array_slice($bigint_params, 3, 3), 
                       array_slice($int_params, 3, 3), 
                       array_slice($smallint_params, 3, 3), 
                       array_slice($tinyint_params, 3, 3), 
                       array_slice($decimal_params, 3, 3), 
                       array_slice($numeric_params, 3, 3), 
                       array_slice($money_params, 3, 3), 
                       array_slice($smallmoney_params, 3, 3), 
                       array_slice($bit_params, 3, 3));

$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
if ($stmt1 === false || $stmt2 === false) {
    echo "Failed to insert rows.\n";
    die(print_r(sqlsrv_errors(), true));
}


// create table for approximate numerics
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('dbo.test_AE_appnum', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE_appnum]");
if ($stmt === false) {
    echo "Failed to drop table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_create = "CREATE TABLE dbo.test_AE_appnum([normFloat] [float], [encDetFloat] [float], [encRandFloat] [float],
                                               [normReal] [real], [encDetReal] [real], [encRandReal] [real])";

$stmt = sqlsrv_query($conn, $sql_create);
if ($stmt === false) {
    echo "Failed to create table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_insert = "INSERT INTO dbo.test_AE_appnum VALUES (?, ?, ?, ?, ?, ?)";

$params1 = array_merge(array_slice($float_params, 0, 3), 
                       array_slice($real_params, 0, 3));
                       
$params2 = array_merge(array_slice($float_params, 3, 3), 
                       array_slice($real_params, 3, 3));

$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
if ($stmt1 === false || $stmt2 === false) {
    echo "Failed to insert rows.\n";
    die(print_r(sqlsrv_errors(), true));
}

// create table for date and time
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('dbo.test_AE_datetime', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE_datetime]");
if ($stmt === false) {
    echo "Failed to drop table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_create = "CREATE TABLE dbo.test_AE_datetime([normDate] [date], [encDetDate] [date], [encRandDate] [date],
                                                       [normDatetime2] [datetime2], [encDetDatetime2] [datetime2], [encRandDatetime2] [datetime2],
                                                       [normDatetime] [datetime], [encDetDatetime] [datetime], [encRandDatetime] [datetime],
                                                       [normDatetimeoffset] [datetimeoffset], [encDetDatetimeoffset] [datetimeoffset], [encRandDatetimeoffset] [datetimeoffset],
                                                       [normTime] [time], [encDetTime] [time], [encRandTime] [time])";

$stmt = sqlsrv_query($conn, $sql_create);
if ($stmt === false) {
    echo "Failed to create table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_insert = "INSERT INTO dbo.test_AE_datetime VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params1 = array_merge(array_slice($date_params, 0, 3), 
                       array_slice($datetime2_params, 0, 3), 
                       array_slice($datetime_params, 0, 3), 
                       array_slice($datetimeoffset_params, 0, 3), 
                       array_slice($time_params, 0, 3));
                       
$params2 = array_merge(array_slice($date_params, 3, 3), 
                       array_slice($datetime2_params, 3, 3), 
                       array_slice($datetime_params, 3, 3), 
                       array_slice($datetimeoffset_params, 3, 3), 
                       array_slice($time_params, 3, 3));

$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
if ($stmt1 === false || $stmt2 === false) {
    echo "Failed to insert rows.\n";
    die(print_r(sqlsrv_errors(), true));
}

// create table for character strings
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('dbo.test_AE_char', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE_char]");
if ($stmt === false) {
    echo "Failed to drop table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_create = "CREATE TABLE dbo.test_AE_char([normChar] [char](10), [encDetChar] [char](10), [encRandChar] [char](10),
                                                       [normVarchar] [varchar](50), [encDetVarchar] [varchar](50), [encRandVarchar] [varchar](50),
                                                       [normVarcharmax] [varchar](max), [encDetVarcharmax] [varchar](max), [encRandVarcharmax] [varchar](max))";

$stmt = sqlsrv_query($conn, $sql_create);
if ($stmt === false) {
    echo "Failed to create table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_insert = "INSERT INTO dbo.test_AE_char VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params1 = array_merge(array_slice($char_params, 0, 3), 
                       array_slice($varchar_params, 0, 3), 
                       array_slice($varcharmax_params, 0, 3));
                       
$params2 = array_merge(array_slice($char_params, 3, 3), 
                       array_slice($varchar_params, 3, 3), 
                       array_slice($varcharmax_params, 3, 3));

$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
if ($stmt1 === false || $stmt2 === false) {
    echo "Failed to insert rows.\n";
    die(print_r(sqlsrv_errors(), true));
}

// create table for unicode character strings
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('dbo.test_AE_unichar', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE_unichar]");
if ($stmt === false) {
    echo "Failed to drop table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_create = "CREATE TABLE dbo.test_AE_unichar([normNchar] [nchar](10), [encDetNchar] [nchar](10), [encRandNchar] [nchar](10),
                                                       [normNvarchar] [nvarchar](50), [encDetNvarchar] [nvarchar](50), [encRandNvarchar] [nvarchar](50),
                                                       [normNvarcharmax] [nvarchar](max), [encDetNvarcharmax] [nvarchar](max), [encRandNvarcharmax] [nvarchar](max))";

$stmt = sqlsrv_query($conn, $sql_create);
if ($stmt === false) {
    echo "Failed to create table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_insert = "INSERT INTO dbo.test_AE_unichar VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params1 = array_merge(array_slice($nchar_params, 0, 3), 
                       array_slice($nvarchar_params, 0, 3), 
                       array_slice($nvarcharmax_params, 0, 3));
                       
$params2 = array_merge(array_slice($nchar_params, 3, 3), 
                       array_slice($nvarchar_params, 3, 3), 
                       array_slice($nvarcharmax_params, 3, 3));

$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
if ($stmt1 === false || $stmt2 === false) {
    echo "Failed to insert rows.\n";
    die(print_r(sqlsrv_errors(), true));
}

/*
// create table for binary strings
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('dbo.test_AE_bin', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE_bin]");
if ($stmt === false) {
    echo "Failed to drop table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_create = "CREATE TABLE dbo.test_AE_bin([normBinary] [binary](10), [encDetBinary] [binary](10), [encRandBinary] [binary](10),
                                                       [normVarbinary] [varbinary](50), [encDetVarbinary] [varbinary](50), [encRandVarbinary] [varbinary](50),
                                                       [normVarbinarymax] [varbinary](max), [encDetVarbinarymax] [varbinary](max), [encRandVarbinarymax] [varbinary](max))";

$stmt = sqlsrv_query($conn, $sql_create);
if ($stmt === false) {
    echo "Failed to create table.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql_insert = "INSERT INTO dbo.test_AE_bin VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params1 = array_merge(array_slice($binary_params, 0, 3), 
                       array_slice($varbinary_params, 0, 3), 
                       array_slice($varbinarymax_params, 0, 3));
                       
$params2 = array_merge(array_slice($binary_params, 3, 3), 
                       array_slice($varbinary_params, 3, 3), 
                       array_slice($varbinarymax_params, 3, 3));

$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
if ($stmt1 === false || $stmt2 === false) {
    echo "Failed to insert rows.\n";
    die(print_r(sqlsrv_errors(), true));
}
*/

sqlsrv_free_stmt($stmt);
sqlsrv_free_stmt($stmt1);
sqlsrv_free_stmt($stmt2);
sqlsrv_close($conn);

?>