--TEST--
Test sqlsrv_num_rows method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require 'MsCommon.inc';
    $conn = Connect();

    if ( !$conn )
    {
        FatalError( "Failed to connect." );
    }

    $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('utf16invalid', 'U') IS NOT NULL DROP TABLE utf16invalid" );
    $stmt = sqlsrv_query( $conn, "CREATE TABLE utf16invalid (id int identity, c1 nvarchar(100))");
    if( $stmt === false ) 
    {
        die( print_r( sqlsrv_errors(), true ));
    }

    $stmt = sqlsrv_query( $conn, "INSERT INTO utf16invalid (c1) VALUES ('TEST')");
    if( $stmt === false )
    { 
        die( print_r( sqlsrv_errors()));
    }
    $stmt = sqlsrv_query( $conn, "SELECT * FROM utf16invalid", array(), array("Scrollable" => SQLSRV_CURSOR_KEYSET ));
    $row_nums = sqlsrv_num_rows($stmt);

    echo $row_nums;

    $stmt = sqlsrv_query( $conn, "DROP TABLE utf16invalid" );
    if( $stmt === false)
    {
        die( print_r( sqlsrv_errors()));
    }

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );

?>

--EXPECT--
1