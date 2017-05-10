--TEST--
A test for a simple query
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );

    $conn = Connect();
    if (!$conn)
    {
        FatalError("Failed to connect");
    }
    $stmt = sqlsrv_query($conn, "SELECT * FROM [cd_info]");
    if (! $stmt)
    {
        FatalError("Failed to select from cd_info");
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
        
    echo "Test successful<br/>\n";
?> 
--EXPECT--
Test successful<br/>