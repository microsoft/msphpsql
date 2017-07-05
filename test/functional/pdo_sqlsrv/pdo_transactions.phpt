--TEST--
Test Transactions.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
 
// commit
function test1($conn)
{
    global $table1;
    $conn->beginTransaction();
    $stmt = $conn->query( "Insert into " .$table1 . " (NCharCol, IntCol) values ('NCharCol3', 3)" );
    $conn->commit();
    echo "\nTest 1 Passed]\n";
}
  
// rollback
function test2($conn)
{
    global $table1;
    $conn->beginTransaction();
    $stmt = $conn->query( "Insert into " .$table1 . " (NCharCol, IntCol) values ('NCharCol3', 3)" );
    $conn->rollBack();
    echo "Test 2 Passed\n";
}
 
// commit
function test3($conn)
{
    global $table1;
    $conn->beginTransaction();
    $stmt = $conn->query( "Insert into " .$table1 . " (NCharCol, IntCol) values ('NCharCol3', 3)" );
    $conn->commit();
    echo "Test 3 Passed\n";
}
 
// Rollback twice. Verify that error is thrown
function test4($conn)
{
    try {
        global $table1;
        $conn->beginTransaction();
        $stmt = $conn->query( "Insert into " .$table1 . " (NCharCol, IntCol) values ('NCharCol3', 3)" );
        $conn->rollBack();
        $conn->rollBack();
    }
    catch( PDOException $e )
    {
        echo "Test4: ". $e->getMessage() . "\n";
    }
}
 
// Commit twice Verify that error is thrown
function test5($conn)
{
    try {
        global $table1;
        $conn->beginTransaction();
        $stmt = $conn->query( "Insert into " .$table1 . " (NCharCol, IntCol) values ('NCharCol3', 3)" );
        $conn->commit();
        $conn->commit();
    }
    catch( PDOException $e )
    {
        echo "Test5: ". $e->getMessage() . "\n";
    }
}
 
// begin transaction twice. Verify that error is thrown
function test6($conn)
{
    try {
        $conn->beginTransaction();
        $conn->beginTransaction();
    }
    catch( PDOException $e )
    {
        echo "Test6: ". $e->getMessage() . "\n";
    }
}

try 
{      
   $db = connect();
   create_and_insert_table1( $db );
   test1($db);
   test2($db);
   test3($db);
   test4($db);
   test5($db);
   test6($db);
  
}

catch( PDOException $e ) {

    var_dump( $e );
    exit;
}


?> 
--EXPECT--
Test 1 Passed]
Test 2 Passed
Test 3 Passed
Test4: There is no active transaction
Test5: There is no active transaction
Test6: There is already an active transaction