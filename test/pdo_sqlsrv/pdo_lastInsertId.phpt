--TEST--
Test the PDO::lastInsertId() method.
--SKIPIF--
--FILE--
<?php
  
require_once("autonomous_setup.php");
   
try 
{         
    $database = "tempdb";
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
	
    $conn->exec("IF OBJECT_ID('tmp_table1', 'U') IS NOT NULL DROP TABLE [tmp_table1]");	
    $conn->exec("IF OBJECT_ID('tmp_table2', 'U') IS NOT NULL DROP TABLE [tmp_table2]");	
    $conn->exec("IF OBJECT_ID('tmp_table3', 'U') IS NOT NULL DROP TABLE [tmp_table3]");	
    $conn->exec('CREATE TABLE tmp_table1(id INT IDENTITY(100,2), val INT)');
    $conn->exec('CREATE TABLE tmp_table2(id INT IDENTITY(200,2), val INT)');
    $conn->exec('CREATE TABLE tmp_table3(id INT, val INT)');
	
    $conn->exec('INSERT INTO tmp_table1 VALUES(1)');
    $conn->exec('INSERT INTO tmp_table2 VALUES(2)');
    $id = $conn->lastInsertId();
    var_dump($id);
	
    $conn->exec('INSERT INTO tmp_table2 VALUES(3)');
    $conn->exec('INSERT INTO tmp_table1 VALUES(4)');
    $id = $conn->lastInsertId();
    var_dump($id);
	
    // Should return empty string as the table does not have an IDENTITY column.
    $conn->exec('INSERT INTO tmp_table3 VALUES(1,1)');
    $id = $conn->lastInsertId();
    var_dump($id);
		
    // clean up
    $conn->exec('DROP TABLE tmp_table1');
    $conn->exec('DROP TABLE tmp_table2');
    $conn->exec('DROP TABLE tmp_table3');
}

catch( PDOException $e ) {
    var_dump( $e );
    exit;
}


?> 
--EXPECT--
string(3) "200"
string(3) "102"
string(0) ""
