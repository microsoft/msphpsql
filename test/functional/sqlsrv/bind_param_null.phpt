--TEST--
BindParam for NULL values.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require( 'MsCommon.inc' );
require ('sqlsrv_test_base.inc');

sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

function bind_params_with_null($conn)
{
    global $table1;
	$sql =  " INSERT INTO " .$table1 . " 
           ([BigIntCol]
           ,[BinaryCol]
           ,[BitCol]
           ,[CharCol]
           ,[DateCol]
           ,[DateTimeCol]
           ,[DateTime2Col]
           ,[DTOffsetCol]
           ,[DecimalCol]
           ,[FloatCol]
           ,[ImageCol]
           ,[IntCol]
           ,[MoneyCol]
           ,[NCharCol]
           ,[NTextCol]
           ,[NumCol]
           ,[NVarCharCol]
           ,[NVarCharMaxCol]
           ,[RealCol]
           ,[SmallDTCol]
           ,[SmallIntCol]
           ,[SmallMoneyCol]
           ,[TextCol]
           ,[TimeCol]
           ,[TinyIntCol]
           ,[Guidcol]
           ,[VarbinaryCol]
           ,[VarbinaryMaxCol]
           ,[VarcharCol]
           ,[VarcharMaxCol]
           ,[XmlCol])
     VALUES (?,?,?,?,?,?,?,?,?,? /*10*/,?,?,?,?,?,?,?,?,?,? /*20*/, ?,?,?,?,?,?,?,?,?,?, ? /*31*/)";

    $param = null;
    $params = array( $param, 
		array( $param, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)), 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, /*10*/ 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, /*20*/
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		$param, 
		array( $param, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)), 
		array( $param, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)), 
		$param, 
		$param, /*30*/
		$param);
		 	
	$stmt = sqlsrv_query($conn, $sql, $params); 
	
	if($stmt === false ) {
		print_r( sqlsrv_errors() );  
		die ("Statement creation failed");
	}
}

$conn = Connect();
create_table1($conn);
bind_params_with_null($conn);
echo "Test Succeeded";

?>

--EXPECTF--

Test Succeeded