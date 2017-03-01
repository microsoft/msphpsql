--TEST--
LastInsertId returns the last sequences operating on the same table
--SKIPIF--
--FILE--
<?php  
require_once("autonomous_setup.php");

try{
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $username, $password);

    $tableName = "table1";
	$sequence1 = "sequence1";
	$sequence2 = "sequenceNeg1";
    
    $stmt = $conn->query("IF OBJECT_ID('$tableName', 'U') IS NOT NULL DROP TABLE $tableName");
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
    $lastSeq = $conn->lastInsertId($sequence1);  
    echo ("Last Sequence: $lastSeq\n");
	
	// return the last sequence number of 'sequenceNeg1'
    $lastSeq = $conn->lastInsertId($sequence2);  
    echo ("Last Sequence: $lastSeq\n");
	
	// providing a table name in lastInsertId should return an empty string
	$lastSeq = $conn->lastInsertId($tableName);
	echo ("Last Sequence: $lastSeq\n");

    $stmt = $conn->query("DROP TABLE $tableName");
    $stmt = $conn->query("DROP SEQUENCE $sequence1");
	$stmt = $conn->query("DROP SEQUENCE $sequence2");

    $stmt = null;
    $conn = null;
}
catch (Exception $e){
    echo "Exception $e\n";
}
   
?>
--EXPECT--
Last Sequence: 3
Last Sequence: 198
Last Sequence: