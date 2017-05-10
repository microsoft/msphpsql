--TEST--
PDO Drivers Info Test
--DESCRIPTION--
Verifies the functionality of "PDO:getAvailableDrivers()”.
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

    $testName = "PDO - Drivers";
    StartTest($testName);

    $drivers = PDO::getAvailableDrivers();
    if (in_array("sqlsrv", $drivers))
    {
        $count = count($drivers);
        for ($i = 0; $i < $count; $i++)
        {
            Trace("Driver #".($i + 1).": ".$drivers[$i]."\n");
        }
    }
    else
    {
        printf("$PhpDriver is missing.\n");
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
Test "PDO - Drivers" completed successfully.