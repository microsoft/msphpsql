--TEST--
GitHub issue 900 - output parameter displays data from memory when not finalized
--DESCRIPTION--
This test verifies that when there is an active resultset and output parameter not finalized, it should not show any data from client memory. This test does not work with AlwaysEncrypted because the output param is not assigned in the stored procedure.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

function getOutputParam($conn, $storedProcName, $dataType, $inout)
{
    $size = rand(1000, 4000);   // The maximum anticipated size is 8000 for wide chars

    try {
        $output = null;
        $stmt = $conn->prepare("$storedProcName @OUTPUT = :output");
        if ($inout) {
            $paramType = PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT;
        } else {
            $paramType = PDO::PARAM_STR;
        }
        $stmt->bindParam('output', $output, $paramType, $size);

        $stmt->execute();

        // The output param should be doubled in size for wide characters.
        // However, it should not contain any data so after trimming it
        // should be merely an empty string because it was originally set to null
        $len = strlen($output);
        $result = trim($output);

        if ($len != ($size * 2) || $result !== "" ) {
            echo "Unexpected output param for $dataType: ";
            var_dump($output);
        }
        
        $stmt->closeCursor();
        if (!is_null($output)) {
            echo "Output param should be null when finalized!";
        }
        unset($stmt);
    } catch (PdoException $e) {
        echo $e->getMessage() . PHP_EOL;
    }
}

try {
    // This helper method sets PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION
    // $conn = connect();
    $conn = new PDO( "sqlsrv:server=$server; Database = $databaseName", $uid, $pwd);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    $dataTypes = array("VARCHAR(256)", "VARCHAR(512)", "VARCHAR(max)", "NVARCHAR(256)", "NVARCHAR(512)", "NVARCHAR(max)");
    for ($i = 0, $p = 3; $i < count($dataTypes); $i++, $p++) {
        // Create the stored procedure first
        $storedProcName = "spNullOutputParam" . $i;
        $procArgs = "@OUTPUT $dataTypes[$i] OUTPUT";
        $procCode = "SELECT 1, 2, 3";

        createProc($conn, $storedProcName, $procArgs, $procCode);
        getOutputParam($conn, $storedProcName, $dataTypes[$i], false);
        getOutputParam($conn, $storedProcName, $dataTypes[$i], true);

        // Drop the stored procedure
        dropProc($conn, $storedProcName);
    }

    echo "Done\n";

    unset($conn);
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}
?>
--EXPECT--
Done
