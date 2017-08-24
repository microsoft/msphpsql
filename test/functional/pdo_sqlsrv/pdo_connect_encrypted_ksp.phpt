--TEST--
Fetch data from a prepopulated test table given a custom keystore provider
--SKIPIF--
<?php require('skipif_server_old.inc'); ?>
--FILE--
<?php
    require( 'MsSetup.inc' );
    require( 'AE_Ksp.inc' );

    $ksp_path = getKSPpath();

    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreProvider = $ksp_path; ";
    $connectionInfo .= "CEKeystoreName = $ksp_name; "; 
    $connectionInfo .= "CEKeystoreEncryptKey = $encrypt_key; ";

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
   
    $tsql = "SELECT * FROM CustomKSPTestTable";
    $stmt = $conn->query($tsql);
    while ($row = $stmt->fetch(PDO::FETCH_NUM))
    {
        echo "c1=" . $row[0] . "\tc2=" . $row[1] . "\n";
    }            
    
    $stmt = null;
    $conn = null;

    echo "Done\n";

?>
--EXPECT--
Connected successfully with ColumnEncryption enabled and KSP specified.
c1=1	c2=Sample data 0 for column 2
c1=12	c2=Sample data 1 for column 2
c1=23	c2=Sample data 2 for column 2
c1=34	c2=Sample data 3 for column 2
c1=45	c2=Sample data 4 for column 2
c1=56	c2=Sample data 5 for column 2
c1=67	c2=Sample data 6 for column 2
c1=78	c2=Sample data 7 for column 2
c1=89	c2=Sample data 8 for column 2
c1=100	c2=Sample data 9 for column 2
Done