--TEST--
starts a transaction, delete rows and rollback the transaction then
starts a transaction, delete rows and commit
--SKIPIF--

--FILE--
<?php
    require_once("autonomous_setup.php");

    $conn = new PDO( "sqlsrv:Server=$serverName; Database = tempdb ", $username, $password);
    
    $conn->exec("IF OBJECT_ID('Table1', 'U') IS NOT NULL DROP TABLE Table1");
    $conn->exec("CREATE TABLE Table1(col1 CHARACTER(1), col2 CHARACTER(1))");
   
    $ret = $conn->exec("INSERT INTO Table1(col1, col2) VALUES('a', 'b')");
    $ret = $conn->exec("INSERT INTO Table1(col1, col2) VALUES('a', 'c')");
   
    //revert the inserts but roll back
    $conn->beginTransaction();
    $rows = $conn->exec("DELETE FROM Table1 WHERE col1 = 'a'");
    $conn->rollback();
    $stmt = $conn->query("SELECT * FROM Table1");
    
    // Table1 should still have 2 rows since delete was rolled back
    if ( count( $stmt->fetchAll() ) == 2 )
        echo "Transaction rolled back successfully\n";
    else
        echo "Transaction failed to roll back\n";
        
    //revert the inserts then commit
    $conn->beginTransaction();
    $rows = $conn->exec("DELETE FROM Table1 WHERE col1 = 'a'");
    $conn->commit();
    echo $rows." rows affected\n";
    
    $stmt = $conn->query("SELECT * FROM Table1");
    if ( count( $stmt->fetchAll() ) == 0 )
        echo "Transaction committed successfully\n";
    else
        echo "Transaction failed to commit\n";
   
    //drop the created temp table
    $conn->exec("DROP TABLE Table1");
   
    //free statement and connection
    $stmt = NULL;
    $conn = NULL;
?>
--EXPECT--
Transaction rolled back successfully
2 rows affected
Transaction committed successfully