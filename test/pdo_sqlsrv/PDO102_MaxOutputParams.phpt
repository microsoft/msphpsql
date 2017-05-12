--TEST--
PDO - Max Output Params Test
--DESCRIPTION--
Fetch data as VARCHAR(MAX)
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function MaxOutputParamsTest($expected, $length)
{
    include 'MsSetup.inc';

    $outstr = null;

    $conn = Connect();

    CreateProc(
        $conn,
        "EXEC_TEST",
        "@OUT varchar(80) output",
        "SET NOCOUNT ON; select @OUT = '$expected'; return (0)
        ");

    $sql = "execute EXEC_TEST ?";

    $stmt = $conn->prepare($sql);

    if ($length)
    {   
        $stmt->bindParam(1, $outstr, PDO::PARAM_STR, 10);
    }
    else
    {
        $stmt->bindParam(1, $outstr, PDO::PARAM_STR, 3);
    }

    $stmt->execute();

    echo "Expected: $expected Received: $outstr\n";

    if ($outstr !== $expected)
    {
        print_r($stmt->errorInfo());
        return(-1);
    }

    return(0);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    $failed = null;

    $testName = "PDO - Max Output Params Test";

    StartTest($testName);

    $failed |= MaxOutputParamsTest("abc", null);
    $failed |= MaxOutputParamsTest("abc", -1);

    if ($failed)
        FatalError("Possible Regression: Value returned as VARCHAR(MAX) truncated");

    EndTest($testName);
}

Repro();
?>
--EXPECT--
Expected: abc Received: abc
Expected: abc Received: abc
Test "PDO - Max Output Params Test" completed successfully.
