--TEST--
Test the PDO::exec() method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
   
try 
{         
    $db = connect();
    
    $sql = "CREATE TABLE #tmp_table(id INT NOT NULL PRIMARY KEY, val VARCHAR(10))";
    $numRows = $db->exec($sql);
    if($numRows === false)
    {
        die("Create table failed\n");
    }
    var_dump($numRows);

    $sql = "INSERT INTO #tmp_table VALUES(1, 'A')";
    $numRows = $db->exec($sql);
    var_dump($numRows);

    $sql = "INSERT INTO #tmp_table VALUES(2, 'B')";
    $numRows = $db->exec($sql);
    var_dump($numRows);

    $numRows = $db->exec("UPDATE #tmp_table SET val = 'X' WHERE id > 0");
    var_dump($numRows);
    
    $numRows = $db->exec("DELETE FROM #tmp_table");
    var_dump($numRows);
    
}

catch( PDOException $e ) {
    var_dump( $e );
    exit;
}


?> 
--EXPECT--
int(0)
int(1)
int(1)
int(2)
int(2)
