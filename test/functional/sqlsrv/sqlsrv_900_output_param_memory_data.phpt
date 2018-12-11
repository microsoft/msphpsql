--TEST--
GitHub issue 900 - output parameter displays data from memory when not finalized
--DESCRIPTION--
This test verifies that when there is an active resultset and output parameter not finalized, it should not show any data from client memory. This test does not work with AlwaysEncrypted because
the output param is not assigned in the stored procedure.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$size = 30;

function getOutputParam($conn, $storedProcName, $inout, $isVarchar, $isMax)
{
    global $size;
    
    $output = null;
    $dir = ($inout)? SQLSRV_PARAM_INOUT : SQLSRV_PARAM_OUT;
    $dataType = ($isVarchar)? "SQLSRV_SQLTYPE_VARCHAR" : "SQLSRV_SQLTYPE_NVARCHAR";
    $sqlType = ($isMax)? call_user_func($dataType, 'max') : call_user_func($dataType, $size);

    $stmt = sqlsrv_prepare($conn, "$storedProcName @OUTPUT = ?", array(array(&$output, $dir, null, $sqlType)));
    if (!$stmt) {
        fatalError("getOutputParam: failed when preparing to call $storedProcName");
    }
    if (!sqlsrv_execute($stmt)) {
        fatalError("getOutputParam: failed to execute procedure $storedProcName");
    }

    // The output param should be doubled in size for wide characters;
    // for max fields it should be the maximum anticipated size 8000.
    // However, it should not contain any data so after trimming it
    // should be merely an empty string
    $len = strlen($output);
    $expectedLen = ($isMax)? 8000 :  ($size * 2);
    $result = trim($output);

    if ($len != $expectedLen || $result !== "" ) {
        echo "Unexpected output param for $dataType, $isMax: ";
        var_dump($output);
    }
    
    sqlsrv_next_result($stmt);
    if (!is_null($output)) {
        echo "Output param should be null when finalized!";
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$conn = connect(array("CharacterSet" => "UTF-8"));
if (!$conn) {
    fatalError("Could not connect.\n");
}

$dataTypes = array("VARCHAR(512)", "VARCHAR(max)", "NVARCHAR(512)", "NVARCHAR(max)");
for ($i = 0, $p = 3; $i < count($dataTypes); $i++, $p++) {
    // Create the stored procedure first
    $storedProcName = "spNullOutputParam" . $i;
    $procArgs = "@OUTPUT $dataTypes[$i] OUTPUT";
    $procCode = "SELECT 1, 2, 3";

    createProc($conn, $storedProcName, $procArgs, $procCode);
    getOutputParam($conn, $storedProcName, false, ($i < 2), ($i % 2 != 0));
    getOutputParam($conn, $storedProcName, true, ($i < 2), ($i % 2 != 0));

    // Drop the stored procedure
    dropProc($conn, $storedProcName);
}

echo "Done\n";

sqlsrv_close($conn);

?>
--EXPECT--
Done
