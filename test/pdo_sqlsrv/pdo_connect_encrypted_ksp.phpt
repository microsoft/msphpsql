--TEST--
Test new connection keywords for specifiying custom keystore provider
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require_once("MsSetup.inc");

    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreProvider = ./odbc-CustomKSP.so;";
    $connectionInfo .= "CEKeystoreName = MyCustomKSPName;"; 
    $connectionInfo .= "CEKeystoreEncryptKey = JHKCWYT06N3RG98J0MBLG4E3;";

    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
        echo "Connected successfully with ColumnEncryption enabled and KSP specified.\n";
    }
    catch( PDOException $e )
    {
        echo "Failed to connect.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }
   
    echo "Done\n";

?>
--EXPECT--
Connected successfully with ColumnEncryption enabled and KSP specified.
Done
