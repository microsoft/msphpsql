--TEST--
Provide name in lastInsertId to retrieve the last sequence number
--SKIPIF--
--FILE--
<?php  
require_once("autonomous_setup.php");

try{
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $username, $password);

	$tableName1 = 'table1'.rand();
    $tableName2 = 'table2'.rand();;
	$sequenceName = "sequence1";
    
    $stmt = $conn->query("IF OBJECT_ID('$tableName1', 'U') IS NOT NULL DROP TABLE $tableName1");
    $stmt = $conn->query("IF OBJECT_ID('$tableName2', 'U') IS NOT NULL DROP TABLE $tableName2");
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
	echo ("Last Sequence: $lastSeq\n");

    // defaults to $tableName2 -- because it returns the last inserted id value
    $lastRow = $conn->lastInsertId();  
	echo ("Last Inserted ID: $lastRow\n");	

    $stmt = $conn->query("DROP TABLE $tableName1");
    $stmt = $conn->query("DROP TABLE $tableName2");
	$stmt = $conn->query("DROP SEQUENCE $sequenceName");

    $stmt = null;
    $conn = null;
}
catch (Exception $e){
    echo "Exception $e\n";
}
   
?>
--EXPECT--
Last Sequence: 3
Last Inserted ID: 1