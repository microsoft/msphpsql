--TEST--
This test verifies that GitHub issue #378 is fixed.
--DESCRIPTION--
GitHub issue #378 - output parameters appends garbage info when variable is initialized with different data type
steps to reproduce the issue:
1- create a store procedure with print and output parameter
2- initialize output parameters to a different data type other than the type declared in sp.
3- set the WarningsReturnAsErrors to true
4 - call sp.
--FILE--
<?php
require_once('MsCommon.inc');

$conn = connect();
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
//----------------Main---------------------------
$procName = GetTempProcName();
createSP($conn, $procName);

sqlsrv_configure('WarningsReturnAsErrors', true);
executeSP($conn, $procName);

sqlsrv_configure('WarningsReturnAsErrors', false);
executeSP($conn, $procName);
echo "Done";
//-------------------functions-------------------
function createSP($conn, $procName)
{
    $sp_sql="create proc $procName @p1 integer, @p2 integer, @p3 integer output
	as
	begin
		select @p3 = @p1 + @p2
		print @p3
	end
	";
    $stmt = sqlsrv_query($conn, $sp_sql);
    if ($stmt === false) {
        fatalError("Failed to create stored procedure");
    }
}

function executeSP($conn, $procName)
{
    $expected = 3;
    $v1 = 1;
    $v2 = 2;
    $v3 = 'str';
    $stmt = sqlsrv_query($conn, "{call $procName( ?, ?, ? )}", array( $v1, $v2, array( &$v3, SQLSRV_PARAM_OUT )));
    if ($stmt === false) {
        print_r(sqlsrv_errors(), true);
    }
    if ($v3 != $expected) {
        fatalError("The expected value is $expected, actual value is $v3\n");
    }
}
?>
--EXPECT--
Done
