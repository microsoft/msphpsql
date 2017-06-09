--TEST--
Test new connection keywords for specifiying custom keystore provider
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );  

    require( 'MsSetup.inc' );

    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>"./odbc-CustomKSP.so", 
                             "CEKeystoreName"=>"MyCustomKSPName",
                             "CEKeystoreEncryptKey"=>"JHKCWYT06N3RG98J0MBLG4E3");

    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Failed to connect.\n";
        print_r( sqlsrv_errors() );
    }
    else
    {
        echo "Connected successfully with ColumnEncryption enabled and KSP specified.\n";
        sqlsrv_close( $conn );
    }
   
    echo "Done\n";

?>
--EXPECT--
Connected successfully with ColumnEncryption enabled and KSP specified.
Done
