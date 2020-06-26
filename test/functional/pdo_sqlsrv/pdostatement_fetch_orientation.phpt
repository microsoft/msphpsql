--TEST--
Test the fetch() method for different fetch orientations with PDO::ATTR_CURSOR set to scrollable.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function runTests($conn, $tableName, $buffered)
{
    $tsql = "SELECT val FROM $tableName ORDER BY id";
    $options = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL);
    if ($buffered) {
        $options = array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED);
    }

    $stmt1 = $conn->prepare($tsql, $options);
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
    if ($row !== false) {
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
    if ($row !== false) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_LAST );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
    if ($row !== false) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_LAST );
    if( $row[ 'val' ] != "C" ) {
        throw new Exception( "Not C" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, 1 );
    if ($row !== false) {
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
    if ($row !== false) {
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
    if ($row !== false) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_PRIOR);
    if ($row !== false) {
        throw new Exception( "Not false" );
    }

    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST );
    if( $row[ 'val' ] != "A" ) {
        throw new Exception( "Not A" );
    }
    $row = $stmt1->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, -1);
    if ($row !== false) {
        throw new Exception( "Not false" );
    }

    unset($stmt1);
}

try {
    $conn1 = connect();

    // Prepare test table
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY", "none"), "val" => "varchar(10)"));
    insertRow($conn1, $tableName, array("id" => 1, "val" => "A"));
    insertRow($conn1, $tableName, array("id" => 2, "val" => "B"));
    insertRow($conn1, $tableName, array("id" => 3, "val" => "C"));

    // Query table and retrieve data
    runTests($conn1, $tableName, false);
    runTests($conn1, $tableName, true);
    
    // Cleanup
    dropTable($conn1, $tableName);
    unset($conn1);

    echo "Test 'PDO Statement - Fetch Scrollable' completed successfully.\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test 'PDO Statement - Fetch Scrollable' completed successfully.