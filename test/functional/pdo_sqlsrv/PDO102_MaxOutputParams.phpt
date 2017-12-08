--TEST--
PDO - Max Output Params Test
--DESCRIPTION--
Fetch data as VARCHAR(MAX)
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require_once("skipif_mid-refactor.inc"); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function maxOutputParamsTest($expected, $length)
{
    $outstr = null;
    $conn = connect();

    $procName = "EXEC_TEST";
    dropProc($conn, $procName);
    $conn->query("CREATE PROC [$procName] (@OUT varchar(80) output) AS BEGIN
                  SET NOCOUNT ON; select @OUT = '$expected'; return(0) END");

    $sql = "execute EXEC_TEST ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $outstr, PDO::PARAM_STR, $length);
    $stmt->execute();

    echo "Expected: $expected Received: $outstr\n";

    $failed = false;
    if ($outstr !== $expected) {
        print_r($stmt->errorInfo());
        $failed = true;
    }
    return $failed;
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
$failed = false;

$failed |= maxOutputParamsTest("abc", 3);
$failed |= maxOutputParamsTest("abc", 10);

if ($failed) {
    fatalError("Possible Regression: Value returned as VARCHAR(MAX) truncated");
}
?>
--EXPECT--
Expected: abc Received: abc
Expected: abc Received: abc
