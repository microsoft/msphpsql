--TEST--
Test the fetch() method for different fetch orientations with PDO::ATTR_CURSOR set to scrollable.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once 'MsCommon.inc';

function FetchAll($execMode, $fetchMode)
{
    require_once 'MsCommon.inc';
    require 'MsSetup.inc';

    $testName = "PDO Statement - Fetch Scrollable";
    StartTest($testName);

    $conn1 = connect();

    // Prepare test table
    $dataCols = "id, val";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'C'", null);

    // Query table and retrieve data
    $stmt1 = $conn1->prepare( "SELECT val FROM $tableName", array( PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ));
    
    $stmt1->execute();  
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_LAST );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR );
    if( $row[ 'val' ] != "B" ) {
        throw new Exception( "Not B" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR );
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }
 
    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_LAST );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, -1 );
    if( $row[ 'val' ] != "B" ) {
        throw new Exception( "Not B" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, -1 );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, -1 );
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();  
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_LAST );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }
    
    $stmt1->execute();  
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_LAST );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, 1 );
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }
    
    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, 2 );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR);
    if( $row[ 'val' ] != "B" ) {
        throw new Exception( "Not B" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, 1 );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, 2 );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, 0 );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, -1 );
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
    if( $row[ 'val' ] != "B" ) {
        throw new Exception( "Not B" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR);
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, 1 );
    if( $row[ 'val' ] != "B" ) {
        throw new Exception( "Not B" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR);
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }
    
    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, -1);
    if( $row[ 'val' ] != false ) {
        throw new Exception( "Not false" );
    }
    
    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

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
        FetchAll(false, PDO::FETCH_BOTH);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO Statement - Fetch Scrollable" completed successfully.