--TEST--
PDO - Quote Null Terminator
--DESCRIPTION--
Verifies the functionality of PDO::Quote, ensuring that the returned string
length does not include the null-terminator.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function QuoteTest()
{
    include 'MsSetup.inc';

    $testName = "PDO - Quote Null Terminator";
    StartTest($testName);

    Setup();

    $testString = "This is a test string";

    $expectedString = "'This is a test string'ASDF";

    $conn = Connect();

    $returnString = $conn->Quote($testString) . "ASDF";

    if ($returnString !== $expectedString)
    {
        echo "Test String: $testString\nExpected String: $expectedString\nQuoted String: $returnString\n";

        FatalError("Possible Regression: Quoted string may have null-terminator");
    }

    EndTest($testName);
}



//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        QuoteTest();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO - Quote Null Terminator" completed successfully.
