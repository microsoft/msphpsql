--TEST--
Test the PDO::getAvailableDrivers() method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
 
try 
{      
    // Do not print anything, as the result will be different for each computer
    $result = PDO::getAvailableDrivers();
    echo "Test successful.";
}

catch( PDOException $e ) {
    var_dump( $e );
    exit;
}


?> 
--EXPECT--
Test successful.