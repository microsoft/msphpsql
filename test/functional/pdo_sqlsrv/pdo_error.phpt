--TEST--
Test the PDO::errorCode() and PDO::errorInfo() methods.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
  
try 
{       
    $db = connect();
    // query with a wrong column name.
    $db->query( "Select * from " . $table1 . " where IntColX = 1"   );
}

catch( PDOException $e ) {
    print($db->errorCode());
    echo "\n";
    print_r($db->errorInfo());
    exit;
}


?> 
--EXPECTREGEX--
42S22
Array
\(
    \[0\] => 42S22
    \[1\] => 207
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid column name 'IntColX'\.
\)