--TEST--
This test verifies that GitHub issue #378 is fixed in pdo_sqlsrv.
--DESCRIPTION--
GitHub issue #378 - output parameters appends garbage info when variable is initialized with different data type
steps to reproduce the issue:
1- create a store procedure with print and output parameter
2- initialize output parameters to a different data type other than the type declared in sp.
3- set the WarningsReturnAsErrors to true
4 - call sp.
--FILE--
<?php
require_once("pdo_tools.inc");
require_once("autonomous_setup.php");

$conn = new PDO( "sqlsrv:Server=$serverName; Database = tempdb ", $username, $password);
if (!$conn) {
	print_r($conn->errorInfo());
}
//$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
//----------------Main---------------------------
$procName = GetTempProcName();
createSP($conn, $procName);

//sqlsrv_configure( 'WarningsReturnAsErrors', true );
executeSP($conn, $procName);

//sqlsrv_configure( 'WarningsReturnAsErrors', false );
//executeSP($conn, $procName);
echo "Done";
//-------------------functions-------------------
function createSP($conn, $procName){
	
	$sp_sql="create proc $procName @p1 integer, @p2 integer, @p3 integer output
	as
	begin
		select @p3 = @p1 + @p2
		print @p3
	end
	";
	$stmt = $conn->exec($sp_sql);
	if ($stmt === false) { print("Failed to create stored procedure"); }
}

function executeSP($conn, $procName){
	$expected = 3;
	
	$stmt = $conn->prepare("{call $procName( ?, ?, ? )}");
	$stmt->bindParam(1, $v1);
	$stmt->bindParam(2, $v2);
	$stmt->bindParam(3, $v3, PDO::PARAM_INT, 10);
	$v1 = 1;
	$v2 = 2;
	$v3 = 'str';
    $stmt->execute();
	if (!$stmt) {
        print_r($conn->errorInfo());
    }
	
	if ( $v3 != $expected ) {
		print("The expected value is $expected, actual value is $v3\n");
	}
}
?>
--EXPECT--
Done