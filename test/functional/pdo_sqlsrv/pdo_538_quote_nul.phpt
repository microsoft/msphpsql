--TEST--
Test the PDO::quote() method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';

try 
{      
    $connection = connect(); 
    //$connection->setAttribute( PDO::SQLSRV_ATTR_DIRECT_QUERY, PDO::SQLSRV_ENCODING_SYSTEM );

    $str = "XX\0XX";
    
    print("Original: " . str_replace("\0", "{NUL}", $str) . "\n");
    $str = $connection->quote($str);
    print("Quoted:   " . str_replace("\0", "{NUL}", $str) . "\n");
}

catch( PDOException $e ) {
    die("Connection error: " . $e->getMessage());
}
?> 

--EXPECT--
Original: XX{NUL}XX
Quoted:   'XX{NUL}XX'