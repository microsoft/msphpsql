--TEST--
PDO PHP Info Test
--DESCRIPTION--
Verifies the functionality of PDO with phpinfo().
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function DriversInfo()
{
    include 'MsSetup.inc';

    $testName = "PDO - phpinfo";
    StartTest($testName);

    ob_start();
    phpinfo();
    $info = ob_get_contents();
    ob_end_clean();

    // Check phpinfo() data
    if (stristr($info, "PDO support => enabled") === false)
    {
        printf("PDO is not enabled\n");
    }
    else if (stristr($info, "pdo_sqlsrv support => enabled") === false)
    {
        printf("Cannot find PDO driver line in phpinfo() output\n");
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
        DriversInfo();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO - phpinfo" completed successfully.