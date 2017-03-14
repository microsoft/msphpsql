--TEST--
Test closeCursor with a stmt before/after execute and fetch.
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

try
{
    /* Connect */
    $conn = new PDO("sqlsrv:server=$serverName", $username, $password);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    
    // prepare a stmt but don't execute, then closeCursor.
    $stmt = $conn->prepare("select 123 as 'IntCol'");
    $ret = $stmt->closeCursor();    
    var_dump($ret);
    $ret = $stmt->closeCursor();    
    var_dump($ret);
    
    // prepare a stmt and execute, then closeCursor.
    $stmt = $conn->prepare("select 123 as 'IntCol'");
    $stmt->execute();
    $ret = $stmt->closeCursor();    
    var_dump($ret);
    $ret = $stmt->closeCursor();    
    var_dump($ret);
    
    
    // use two stmt, execute, and fetch, then closeCursor.
    // use one with client side buffering.
    $stmt1 = $conn->query("select 123 as 'IntCol'");
    $stmt2 = $conn->prepare("select 'abc' as 'Charcol'", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $result = $stmt1->fetch(PDO::FETCH_NUM);
    print_r($result[0]); 
    echo "\n";
    $ret = $stmt1->closeCursor();    
    var_dump($ret);
    $stmt2->execute();
    $result = $stmt2->fetch(PDO::FETCH_NUM);
    print_r($result[0]);
    echo "\n";
    $ret = $stmt2->closeCursor();    
    var_dump($ret);
    
    $stmt1 = null;
    $stmt2 = null;
    $stmt = null;
    $conn = null;
    
}

catch( PDOException $e ) {
    var_dump($e);	
	exit;
}
    
print "Done";
?>

--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
123
bool(true)
abc
bool(true)
Done