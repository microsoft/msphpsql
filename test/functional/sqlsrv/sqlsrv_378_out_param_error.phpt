--TEST--
This test verifies that GitHub issue #378 is fixed.
--DESCRIPTION--
GitHub issue #378 - output parameters appends garbage info when variable is initialized with different data type
Steps to reproduce the issue:
1- create a store procedure with print and output parameter
2- initialize output parameters to a different data type other than the type declared in sp.
3- set the WarningsReturnAsErrors to true
4- call sp.
Also check error conditions by passing output parameters NOT by reference.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

//----------------Main---------------------------
$procName = 'test_378';
createSP($conn, $procName);

runTests($conn, $procName, true);
runTests($conn, $procName, false);

dropProc($conn, $procName);
echo "Done\n";

//-------------------functions-------------------
function createSP($conn, $procName)
{
    dropProc($conn, $procName);

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

//-------------------functions-------------------
function runTests($conn, $procName, $warningAsErrors)
{
    sqlsrv_configure('WarningsReturnAsErrors', $warningAsErrors);

    trace("\nWarningsReturnAsErrors: $warningAsErrors\n");
    
    executeSP($conn, $procName, true, false);
    executeSP($conn, $procName, true, true);
    executeSP($conn, $procName, false, false);
    executeSP($conn, $procName, false, true);
}

function compareErrors()
{
    $message = 'Variable parameter 3 not passed by reference (prefaced with an &).  Output or bidirectional variable parameters (SQLSRV_PARAM_OUT and SQLSRV_PARAM_INOUT) passed to sqlsrv_prepare or sqlsrv_query should be passed by reference, not by value.';
    
    $error = sqlsrv_errors()[0]['message'];
    
    if ($error !== $message) {
        print_r(sqlsrv_errors(), true);
        return;
    }
    
    trace("Comparing errors: matched!\n");
}

function executeSP($conn, $procName, $noRef, $prepare)
{
    $expected = 3;
    $v1 = 1;
    $v2 = 2;
    $v3 = 0;

    $res = true;
    $tsql = "{call $procName( ?, ?, ?)}";

    if ($noRef) {
        $params = array($v1, $v2, array($v3, SQLSRV_PARAM_OUT));
    } else {
        $params = array($v1, $v2, array(&$v3, SQLSRV_PARAM_OUT));
    }
    
    trace("No reference: $noRef\n");
    trace("Use prepared stmt: $prepare\n");

    if (AE\isColEncrypted() || $prepare) {
        $stmt = sqlsrv_prepare($conn, $tsql, $params);
        if ($stmt) {
            $res = sqlsrv_execute($stmt);
        } else {
            fatalError("executeSP: failed in preparing statement with reference($noRef)");
        }
        if ($noRef) { 
            if ($res !== false) {
                echo "Expect this to fail!\n";
            } 
            compareErrors();
            return;
        } 
    } else {
        $stmt = sqlsrv_query($conn, $tsql, $params);
        if ($noRef) { 
            if ($stmt !== false) {
                echo "Expect this to fail!\n";
            }
            compareErrors();
            return;
        }
    }
    
    trace("No errors: $v3 and $expected\n");
    // No errors expected
    if ($stmt === false || !$res) {
        print_r(sqlsrv_errors(), true);
    }
    if ($v3 != $expected) {
        fatalError("The expected value is $expected, actual value is $v3\n");
    }
}
?>
--EXPECT--
Done
