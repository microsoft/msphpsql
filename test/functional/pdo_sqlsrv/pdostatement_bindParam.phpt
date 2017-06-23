--TEST--
PDOStatement::BindParam for NULL types and for value types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

//*************************************************************************************

// WHEN LENGTH IS SPECIFIED THAN WE CONSIDER THE TYPE TO BE OUTPUT TYPE.

// THIS IS NOT ALLOWED. SINCE STREAM TYPES CANNOT BE OUTPUT TYPES
//$stmt->bindParam(1, $param, PDO::PARAM_LOB, 10, PDO::SQLSRV_ENCODING_BINARY);

// THIS IS ERROR DUE TO > 8000
//$stmt->bindParam(1, $param, PDO::PARAM_STR, 9000, PDO::SQLSRV_ENCODING_BINARY);

// THIS RESULTS IN INVALID LENGTH ERROR.
//$stmt->bindParam(1, $param, PDO::PARAM_STR, -1, PDO::SQLSRV_ENCODING_BINARY);

// THIS DOES NOT WORK FOR ALL CASES SINCE PDO CALLS BINDPARAM DURING SQLEXECUTE.
//$stmt->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_BINARY );

//*************************************************************************************

require_once 'MsCommon.inc';

function bind_params_with_null($db)
{
	global $table2;
	$sql =  " INSERT INTO " .$table2 . " 
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

	$stmt = $db->prepare($sql);

    $param = null;
    $stmt->bindParam(1, $param);
    $stmt->bindParam(2, $param, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY );
    $stmt->bindParam(3, $param );
    $stmt->bindParam(4, $param );
    $stmt->bindParam(5, $param );
    $stmt->bindParam(6, $param );
    $stmt->bindParam(7, $param );
    $stmt->bindParam(8, $param );
    $stmt->bindParam(9, $param );
    $stmt->bindParam(10, $param );
    $stmt->bindParam(11, $param );
    $stmt->bindParam(12, $param );
	$stmt->bindParam(13, $param );
	$stmt->bindParam(14, $param );
	$stmt->bindParam(15, $param );
	$stmt->bindParam(16, $param );
	$stmt->bindParam(17, $param );
	$stmt->bindParam(18, $param );
	$stmt->bindParam(19, $param );
	$stmt->bindParam(20, $param );
	$stmt->bindParam(21, $param );
	$stmt->bindParam(22, $param );
	$stmt->bindParam(23, $param );
	$stmt->bindParam(24, $param );
	$stmt->bindParam(25, $param );
	$stmt->bindParam(26, $param );
	$stmt->bindParam(27, $param, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY ); //PDO::PARAM_STR OR PDO::PARAM_LOB DOES NOT MATTER
	$stmt->bindParam(28, $param, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY );
	$stmt->bindParam(29, $param );
	$stmt->bindParam(30, $param );
	$stmt->bindParam(31, $param );	
	
	$stmt->execute();
}

function bind_params_int($db)
{
	global $table2;
	$stmt = $db->prepare("SELECT * FROM " . $table2 . " WHERE BigIntCol = :bigIntCol ");
	$int = 1;
	$stmt->bindParam(":bigIntCol", $int, PDO::PARAM_INT);
	$stmt->execute();
}


function bind_params_str($db)
{
	global $table2;
	$stmt = $db->prepare("SELECT * FROM " . $table2 . " WHERE CharCol = :charCol ");
	$char = "STRINGCOL1";
	$stmt->bindParam(":charCol", $char, PDO::PARAM_STR);
	$stmt->execute();
}

function bind_params_bool($db)
{
	global $table2;
	$stmt = $db->prepare("SELECT * FROM " . $table2 . " WHERE BitCol = :bool ");
	$bool = 0;
	$stmt->bindParam(":bool" , $bool, PDO::PARAM_BOOL);
	$stmt->execute();
}

function bind_params_lob($db)
{
	global $table2;
	$stmt = $db->prepare("SELECT * FROM " . $table2 . " WHERE BinaryCol = :lob ");
	$lob = 0;
	$stmt->bindParam(":lob" , $lob, PDO::PARAM_LOB);
	$stmt->execute();
}



try {

   $db = connect();
   create_and_insert_table2( $db );
   bind_params_with_null($db);
   bind_params_int($db);
   bind_params_str($db);
   bind_params_bool($db);
   bind_params_lob($db);
   
   echo "Test Succeeded";
}

catch( PDOException $e ) {
    var_dump( $e );
    exit;
}

?>

--EXPECT--
Test Succeeded
