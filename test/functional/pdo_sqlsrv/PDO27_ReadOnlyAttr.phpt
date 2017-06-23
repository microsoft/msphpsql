--TEST--
PDO Test for read-only attributes
--DESCRIPTION--
Verification of read-only attributes
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ReadOnly()
{
    include 'MsSetup.inc';

    $testName = "PDO Connection - Attribute";
    StartTest($testName);

    $conn1 = Connect();
    $conn1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    CheckAttribute($conn1, "PDO::ATTR_CLIENT_VERSION");
    CheckAttribute($conn1, "PDO::ATTR_DRIVER_NAME");
    CheckAttribute($conn1, "PDO::ATTR_SERVER_INFO");
    CheckAttribute($conn1, "PDO::ATTR_SERVER_VERSION");

    // Cleanup
    $conn1 = null;

    EndTest($testName);
}


function CheckAttribute($conn, $attName)
{
    $att = constant($attName);

    // Attribute value is a non-empty string
    $value1 = $conn->getAttribute($att);

    // Attribute is read-only
    if ($conn->setAttribute($att, $value1) !== false)
    {
        printf("Attribute $attName must be read-only\n");
    }

    // Attribute value should not change
    $value2 = $conn->getAttribute($att);
    if ($value2 !== $value1)
    {
        printf("Value of attribute $attName should not change from '%s' to '%s'\n", $value1, $value2);
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        ReadOnly();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO Connection - Attribute" completed successfully.