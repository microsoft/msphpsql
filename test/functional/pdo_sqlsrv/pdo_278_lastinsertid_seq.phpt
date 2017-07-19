--TEST--
Provide name in lastInsertId to retrieve the last sequence number
--SKIPIF--
--FILE--
<?php  
include 'pdo_tools.inc';
require_once("autonomous_setup.php");
try{
    $database = "tempdb";
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    
    // sequence is only supported in SQL server 2012 and up (or version 11 and up)
    // Output Done once the server version is found to be < 11
    $version_arr = explode(".", $conn->getAttribute(PDO::ATTR_SERVER_VERSION));
    if ($version_arr[0] < 11) {
        echo "Done\n";
    }
    else {
        $tableName1 = GetTempTableName('tab1');
        $tableName2 = GetTempTableName('tab2');
        $sequenceName = 'sequence1';
    
        $stmt = $conn->query("IF OBJECT_ID('$sequenceName', 'SO') IS NOT NULL DROP SEQUENCE $sequenceName");
        $sql = "CREATE TABLE $tableName1 (seqnum INTEGER NOT NULL PRIMARY KEY, SomeNumber INT)";
        $stmt = $conn->query($sql);
        $sql = "CREATE TABLE $tableName2 (ID INT IDENTITY(1,2), SomeValue char(10))";
        $stmt = $conn->query($sql);
    
        $sql = "CREATE SEQUENCE $sequenceName AS INTEGER START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100 CYCLE";
        $stmt = $conn->query($sql);
    
        $ret = $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 20 )");
        $ret = $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 40 )");
        $ret = $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 60 )");
        $ret = $conn->exec("INSERT INTO $tableName2 VALUES( '20' )");
        // return the last sequence number is sequence name is provided
        $lastSeq = $conn->lastInsertId($sequenceName);
        // defaults to $tableName2 -- because it returns the last inserted id value
        $lastRow = $conn->lastInsertId();
        
        if ($lastSeq == 3 && $lastRow == 1) {
            echo "Done\n";
        }
        else {
            echo "sequence value or identity does not match as expected\n";
        }
        $stmt = $conn->query("DROP TABLE $tableName1");
        $stmt = $conn->query("DROP TABLE $tableName2");
        $stmt = $conn->query("DROP SEQUENCE $sequenceName");
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