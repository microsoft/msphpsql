--TEST--
LastInsertId returns the last sequences operating on the same table
--SKIPIF--
--FILE--
<?php
require_once("MsCommon.inc");
require_once("MsSetup.inc");

try{
    $database = "tempdb";
    $conn = new PDO("sqlsrv:Server=$server;Database=$databaseName", $uid, $pwd);
    
    // sequence is only supported in SQL server 2012 and up (or version 11 and up)
    // Output Done once the server version is found to be < 11
    $version_arr = explode(".", $conn->getAttribute(PDO::ATTR_SERVER_VERSION));
    if ($version_arr[0] < 11) {
        echo "Done\n";
    }
    else {
        $tableName = GetTempTableName('tab');
        $sequence1 = 'sequence1';
        $sequence2 = 'sequenceNeg1';
        $stmt = $conn->query("IF OBJECT_ID('$sequence1', 'SO') IS NOT NULL DROP SEQUENCE $sequence1");
        $stmt = $conn->query("IF OBJECT_ID('$sequence2', 'SO') IS NOT NULL DROP SEQUENCE $sequence2");
        $sql = "CREATE TABLE $tableName (ID INT IDENTITY(1,1), SeqNumInc INTEGER NOT NULL PRIMARY KEY, SomeNumber INT)";
        $stmt = $conn->query($sql);
        $sql = "CREATE SEQUENCE $sequence1 AS INTEGER START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100";
        $stmt = $conn->query($sql);
    
        $sql = "CREATE SEQUENCE $sequence2 AS INTEGER START WITH 200 INCREMENT BY -1 MINVALUE 101 MAXVALUE 200";
        $stmt = $conn->query($sql);
        $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence1, 20 )");
        $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence2, 180 )");
        $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence1, 40 )");
        $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence2, 160 )");
        $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence1, 60 )");
        $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence2, 140 )");
        // return the last sequence number of 'sequence1'
        $lastSeq1 = $conn->lastInsertId($sequence1);
    
        // return the last sequence number of 'sequenceNeg1'
        $lastSeq2 = $conn->lastInsertId($sequence2);
    
        // providing a table name in lastInsertId should return an empty string
        $lastSeq3 = $conn->lastInsertId($tableName);
        
        if ($lastSeq1 == 3 && $lastSeq2 == 198 && $lastSeq3 == "") {
            echo "Done\n";
        }
        $stmt = $conn->query("DROP TABLE $tableName");
        $stmt = $conn->query("DROP SEQUENCE $sequence1");
        $stmt = $conn->query("DROP SEQUENCE $sequence2");
        $stmt = null;
    }
    $conn = null;
}
    catch (Exception $e){
    echo "Exception $e\n";
}
   
?>
--EXPECT--
Done