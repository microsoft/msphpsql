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

    $testName = "Regression VSTS 846501";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    // empty parameter array
    $s = sqlsrv_query( $conn1, "DROP TABLE test_bq" );
    $s = sqlsrv_query( $conn1, "CREATE TABLE test_bq (id INT IDENTITY NOT NULL, test_varchar_max varchar(max))" );
    if( $s === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $s = sqlsrv_query( $conn1, "CREATE CLUSTERED INDEX [idx_test_int] ON test_bq (id)" );
    if( $s === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $s = sqlsrv_query( $conn1, "INSERT INTO test_bq (test_varchar_max) VALUES ('ABCD')" );
    if( $s === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }

    $tsql = "select test_varchar_max from test_bq";
    $result = sqlsrv_query( $conn1, $tsql, array(), array( "Scrollable" => SQLSRV_CURSOR_CLIENT_BUFFERED ));

    while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_BOTH))
    {
        print $row['test_varchar_max']."\n";
    }
    
    sqlsrv_query( $conn1, "DROP TABLE test_bq" );
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
ABCD
Test "Regression VSTS 846501" completed successfully.
