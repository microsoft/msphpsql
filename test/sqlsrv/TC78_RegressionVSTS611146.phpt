--TEST--
Data Roundtrip via Stored Proc
--DESCRIPTION--
Verifies that data is not corrupted through a roundtrip via a store procedure.
Checks all character data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function BugRepro()
{
    include 'MsSetup.inc';

    $testName = "Regression VSTS 611146";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    // empty parameter array
    $s = sqlsrv_query( $conn1, "select ?", array( array() ));
    if( $s !== false ) 
    {
        die( "Should have failed." );
    }
    else
    {
        $arr = sqlsrv_errors();
        print_r( $arr[0][2] );
        print_r( "\n" );
    }


    // unknown direction
    $s = sqlsrv_query( $conn1, "select ?", array( array( 1, 1000 ) ));
    if( $s !== false ) 
    {
        die( "Should have failed." );
    }
    else
    {
        $arr = sqlsrv_errors();
        print_r( $arr[0][2] );
        print_r( "\n" );
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
        BugRepro();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Parameter array 1 must have at least one value or variable.
An invalid direction for parameter 1 was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.
Test "Regression VSTS 611146" completed successfully.
