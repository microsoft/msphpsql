<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );  

include "AESetup.inc";

$conn = AEConnect(true);
DropAETable($conn);
CreateAETableTypes($conn, array("bigint", "int", "smallint", "tinyint"));

$sql_insert = "INSERT INTO [dbo].[test_AE] ([normBigint], [encDetBigint], [encRandBigint],
                                            [normInt], [encDetInt], [encRandInt],
                                            [normSmallint], [encDetSmallint], [encRandSmallint],
                                            [normTinyint], [encDetTinyint], [encRandTinyint]) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

//no sqltypes  
//$params1 = array(2147483648, -9223372036854775807, 9223372036854775807, 32768, -2147483647, 2147483647, 256, -32767, 32767, 128, 0, 255); 
//$params2 = array(-2147583649, 4611686017353646080, -4611686017353646080, -32769, 1073725440, -1073725440, -1, 16256, -16256, 96, 64, 162);

//with sqltypes
$params1 = array( array(2147483648, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIGINT),
                 array(-922337203685477580, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIGINT),
                 array(9223372036854775807, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIGINT),
                 array(32768, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                 array(-2147483647, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                 array(2147483647, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                 array(256, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_SMALLINT),
                 array(-32767, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_SMALLINT),
                 array(32767, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_SMALLINT),
                 array(128, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT),
                 array(0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT),
                 array(255, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT));
                 

$params2 = array( array(-2147583649, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIGINT),
                 array(4611686017353646080, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIGINT),
                 array(-4611686017353646080, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_BIGINT),
                 array(-32769, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                 array(1073725440, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                 array(-1073725440, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                 array(-1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_SMALLINT),
                 array(16256, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_SMALLINT),
                 array(-16256, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_SMALLINT),
                 array(96, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT),
                 array(64, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT),
                 array(162, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT));
                 
$stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
sqlsrv_assert($stmt1 === false, "Error in populating table.\n");
$stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
sqlsrv_assert($stmt2 === false, "Error in populating table.\n");

//Fetch encrypted values with ColumnEncryption Enabled
$sql = "SELECT * FROM [dbo].[test_AE]";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_assert($stmt === false, "Error in selecting from table.\n");

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    print_r($row);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

//Fetch encrypted values with ColumnEncryption Disabled
$conn = AEConnect(false);
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_assert($stmt === false, "Error in selecting from table.\n");

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    print_r($row);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>