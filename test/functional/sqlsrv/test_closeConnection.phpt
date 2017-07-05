--TEST--
using an already closed connection.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );

   $conn = Connect();
   
   $queries = array(
        "SET IDENTITY_INSERT [155671] ON",
        "INSERT INTO [155671] (cat_id, cat_title, cat_order) VALUES (14, 'This will be inserted into the db.', 1);",
        "SET IDENTITY_INSERT [155671] OFF",
    );
    
    foreach( $queries as $query) {
    
        $stmt = sqlsrv_query( $conn, $query );
        if( $stmt == false ) {
            var_dump( sqlsrv_errors() );
            die( $query . " caused an error");
        }
    }
    
    sqlsrv_close( $conn );

    echo "Test successful."
?>
--EXPECT--
Test successful.
