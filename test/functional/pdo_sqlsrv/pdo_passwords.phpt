--TEST--
Test password with non alphanumeric characters
--DESCRIPTION--
The first three cases should have no problem connecting. Only the last case fails because the 
right curly brace should be escaped with another right brace.
In Azure we can't set DEFAULT_DATABASE for a login user. For this test to psss must connect
to the test database defined in MsSetup.inc 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require  'MsSetup.inc';

try{
    // Test 1
    $conn = new PDO( "sqlsrv:Server=$server;ConnectionPooling=false;" , "test_password", "! ;4triou"); 
    if(!$conn)
    {
        echo "Test 1: Should have connected.";
    }
}
catch(PDOException $e){
    print_r($e->getMessage() . "\n");
    
}
try{
    // Test 2
    $conn = new PDO( "sqlsrv:Server=$server;ConnectionPooling=false;" , "test_password2", "!}} ;4triou"); 
    if(!$conn)
    {
        echo "Test 2: Should have connected.";
    }
}
catch(PDOException $e){
    print_r($e->getMessage() . "\n");
    
}
try{
    // Test 3
    $conn = new PDO( "sqlsrv:Server=$server;ConnectionPooling=false;" , "test_password3", "! ;4triou}}"); 
    if(!$conn)
    {
        echo "Test 3: Should have connected.";
    }
}
catch(PDOException $e){
    print_r($e->getMessage() . "\n");
    
}
// Test invalid password.
try
{
    // Test 4
    $conn = new PDO( "sqlsrv:Server=$server;ConnectionPooling=false;" , "test_password3", "! ;4triou}"); 
}   
catch( PDOException $e ) {
    print_r( $e->getMessage() );
    exit;
}
  
?> 

--EXPECTREGEX--
SQLSTATE\[IMSSP\]: An unescaped right brace \(}\) was found in either the user name or password\.  All right braces must be escaped with another right brace \(}}\)\.



