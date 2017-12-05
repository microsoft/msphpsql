--TEST--
Fetch encrypted data from a prepopulated test table given a custom keystore provider
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    require_once("MsCommon_mid-refactor.inc");

    try
    {
        $conn = connect();
        echo "Connected successfully with ColumnEncryption disabled and KSP specified.\n";
    }
    catch( PDOException $e )
    {
        echo "Failed to connect.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }
   
    $tbname = KSP_TEST_TABLE;
    $tsql = "SELECT * FROM $tbname";
    $stmt = $conn->query($tsql);
    while ($row = $stmt->fetch(PDO::FETCH_NUM))
    {
        echo "c1=" . $row[0];
        echo "\tc2=" . bin2hex($row[1]);
        echo "\tc3=" . bin2hex($row[2]); 
        echo "\tc4=" . bin2hex($row[3]);
        echo "\n" ;
    }            
    
    unset($stmt);
    unset($conn);

    echo "Done\n";

?>
--EXPECTREGEX--
Connected successfully with ColumnEncryption disabled and KSP specified.
c1=1	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=12	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=23	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=34	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=45	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=56	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=67	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=78	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=89	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=100	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
Done