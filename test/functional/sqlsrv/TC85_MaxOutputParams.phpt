--TEST--
PHP - Max Output Params Test
--DESCRIPTION--
Fetch data as VARCHAR(MAX)
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function MaxOutputParamsTest($buffer, $phptype, $sqltype, $expected)
{
    setup();

    $conn = connect();

    dropProc($conn, "EXEC_TEST");

    createProc(
        $conn,
        "EXEC_TEST",
        "@OUT varchar(80) output",
        "SET NOCOUNT ON; select @OUT = '$expected'; return (0)
    "
    );

    $outstr = $buffer;

    $sql = "execute EXEC_TEST ?";

    $stmt = sqlsrv_prepare($conn, $sql, array(array( &$outstr, SQLSRV_PARAM_OUT, $phptype, $sqltype)));

    sqlsrv_execute($stmt);

    echo "Expected: $expected Received: $outstr\n";

    if ($outstr !== $expected) {
        print_r(sqlsrv_errors(SQLSRV_ERR_ALL));
        return(-1);
    }


    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);

    return(0);
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    $failed = null;

    $testName = "PHP - Max Output Params Test";

    startTest($testName);

    try {
        $failed |= MaxOutputParamsTest("ab", SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('MAX'), "abc");
        $failed |= MaxOutputParamsTest(null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('10'), "abc");
        $failed |= MaxOutputParamsTest(null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('MAX'), "abc");
        $failed |= MaxOutputParamsTest(null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARCHAR('MAX'), "abc");
        $failed |= MaxOutputParamsTest("abc", SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), null, "abc");
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    if ($failed) {
        fatalError("Possible Regression: Value returned as VARCHAR(MAX) truncated");
    }

    endTest($testName);
}

repro();
?>
--EXPECT--
Expected: abc Received: abc
Expected: abc Received: abc
Expected: abc Received: abc
Expected: abc Received: abc
Expected: abc Received: abc
Test "PHP - Max Output Params Test" completed successfully.
