--TEST--
PDO Info Test
--DESCRIPTION--
Verifies the functionality of "getAttribute”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function AttrTest()
{
    include 'MsSetup.inc';

    $testName = "PDO Connection - Attribute Info";
    StartTest($testName);

    $conn1 = Connect();
    ShowInfo($conn1);
    $conn1 = null;

    EndTest($testName);
}

function ShowInfo($conn)
{
    $attributes = array("AUTOCOMMIT",       // Not supported
                "CASE",         // 0
                "CLIENT_VERSION",       // array
                "CONNECTION_STATUS",    // Not supported
                "DRIVER_NAME",      // sqlsrv
                "ERRMODE",          // 0
                    "ORACLE_NULLS",     // 0
                "PERSISTENT",       // false
                "PREFETCH",         // Not supported
                "SERVER_INFO",      // array
                "SERVER_VERSION",       // string
                "STATEMENT_CLASS",      // array
                "STRINGIFY_FETCHES",    // false
                "TIMEOUT");         // Not supported

    foreach ($attributes as $val)
    {
        $att = "PDO::ATTR_$val";
        $attKey = constant($att);
        $attVal = $conn->getAttribute($attKey);

        Trace("$att ($attKey): [$attVal]\n");
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
        AttrTest();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTF--
SQLSTATE[IM001]: Driver does not support this function: driver does not support that attribute