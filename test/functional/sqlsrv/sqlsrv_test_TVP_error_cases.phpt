--TEST--
Test various error cases with invalid Table-valued parameter inputs
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function invokeProc($conn, $proc, $tvpInput, $caseNo, $dir = SQLSRV_PARAM_IN)
{
    if ($dir == SQLSRV_PARAM_IN) {
        $params = array(array($tvpInput, $dir, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE));
    } else {
        $params = array(array(&$tvpInput, $dir, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE));
    }
    
    $stmt = sqlsrv_query($conn, $proc, $params);
    if (!$stmt) {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
        if (!empty($errors)) {
            $count = count($errors);
        }
        for ($i = 0; $i < $count; $i++) {
            echo "Error $caseNo: ";
            echo $errors[$i]['message'] . PHP_EOL;
        }
    }
}

function cleanup($conn, $schema, $tvpType, $procName)
{
    global $dropSchema;
    
    $dropProcedure = dropProcSQL($conn, "[$schema].[$procName]");
    sqlsrv_query($conn, $dropProcedure);

    $dropTableType = dropTableTypeSQL($conn, "[$schema].[$tvpType]");
    sqlsrv_query($conn, $dropTableType);
    
    sqlsrv_query($conn, $dropSchema);
}

sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

$conn = connect(array('CharacterSet'=>'UTF-8'));

// Use a different schema instead of dbo
$schema = 'Sales DB';
$tvpType = 'TestTVP3';
$procName = 'SelectTVP3';

cleanup($conn, $schema, $tvpType, $procName);
// dropProc($conn, 'SelectTVP3');

// $tvpType = 'TestTVP3';
// $dropTableType = dropTableTypeSQL($conn, $tvpType);
// sqlsrv_query($conn, $dropTableType);

// Create table type and a stored procedure
sqlsrv_query($conn, $createSchema);
sqlsrv_query($conn, $createTestTVP3);
sqlsrv_query($conn, $createSelectTVP3);

// Create a TVP input array
$inputs = [
    ['ABC', 12345, null],
    ['DEF', 6789, null],
    ['GHI', null],
];
$str = 'dummy';

// Case (1) - do not provide TVP type name 
$tvpInput = array($inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 1);

// Case (2) - use an empty string as TVP type name 
$tvpInput = array("" => array());
invokeProc($conn, $callSelectTVP3, $tvpInput, 2);

// The TVP name should include the schema
$tvpTypeName = "$schema.$tvpType";

// Case (3) - null inputs
$tvpInput = array($tvpTypeName => null);
invokeProc($conn, $callSelectTVP3, $tvpInput, 3);

// Case (4) - not using array as inputs
$tvpInput = array($tvpTypeName => 1);
invokeProc($conn, $callSelectTVP3, $tvpInput, 4);

// Case (5) - invalid TVP type name
$tvpInput = array($str => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 5);

// Case (6)  - input rows are not the same size
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 6);

// Case (7) - input row wrong size
unset($inputs);
$inputs = [
    ['ABC', 12345, null, null]
];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 7);

// Case (8) - use string keys
unset($inputs);
$inputs = [
    ['A' => null, null, null]
];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 8);

// Case (9) - a row is not an array
unset($inputs);
$inputs = [null];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 9);

// Case (10) - a column value used a string key
unset($inputs);
$inputs = [
    ['ABC', 12345, "key"=>null]
];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 10);

// Case (11) - invalid input object for a TVP column
class foo
{
    function do_foo(){}
}
$bar = new foo;
unset($inputs);
$inputs = [
    ['ABC', 1234, $bar],
    ['DEF', 6789, null],
];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 11);

// Case (12) - invalid input type for a TVP column
unset($inputs);
$inputs = [
    ['ABC', &$str, null],
    ['DEF', 6789, null],
];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 12);

// Case (13) - bind a TVP as an OUTPUT param
invokeProc($conn, $callSelectTVP3, $tvpInput, 13, SQLSRV_PARAM_OUT);

// Case (14) - test UTF-8 invalid/corrupt string for a TVP column
unset($inputs);
$utf8 = str_repeat("41", 8188);
$utf8 = $utf8 . "e38395e38395";
$utf8 = substr_replace($utf8, "fe", 1000, 2);
$utf8 = pack("H*", $utf8);

$inputs = [
    [$utf8, 1234, null],
    ['DEF', 6789, null],
];
$tvpInput = array($tvpTypeName => $inputs);
invokeProc($conn, $callSelectTVP3, $tvpInput, 14);

cleanup($conn, $schema, $tvpType, $procName);

// dropProc($conn, 'SelectTVP3');
// sqlsrv_query($conn, $dropTableType);
sqlsrv_close($conn);

echo "Done" . PHP_EOL;
?>
--EXPECTF--
Error 1: Expect a non-empty string for a Type Name for Table-Valued Param 1
Error 2: Expect a non-empty string for a Type Name for Table-Valued Param 1
Error 3: Invalid inputs for Table-Valued Param 1
Error 4: Invalid inputs for Table-Valued Param 1
Error 5: Failed to get metadata for Table-Valued Param 1
Error 6: For Table-Valued Param 1 the number of values in a row is expected to be 3
Error 7: For Table-Valued Param 1 the number of values in a row is expected to be 3
Error 8: Associative arrays not allowed for Table-Valued Param 1
Error 9: Expect an array for each row for Table-Valued Param 1
Error 10: Associative arrays not allowed for Table-Valued Param 1
Error 11: An invalid type for Table-Valued Param 1 Column 3 was specified
Error 12: An invalid type for Table-Valued Param 1 Column 2 was specified
Error 13: You cannot return data in a table-valued parameter. Table-valued parameters are input-only.
Error 14: An error occurred translating a string for Table-Valued Param 1 Column 1 to UTF-16: %a

Done
