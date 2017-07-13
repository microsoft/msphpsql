--TEST--
password with non alphanumeric characters
--DESCRIPTION--
The first three cases should have no problem connecting. Only the last case fails because the 
right curly brace should be escaped with another right brace.
In Azure for this test to pass do not specify any particular database when connecting
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
 
require 'MsSetup.inc';
function toConnect($options = array()) 
{
    global $server;
    
    // this function makes a connection to the server WITHOUT specifying the database 
    return sqlsrv_connect($server, $options);
}

$conn = toConnect(array( "UID" => "test_password", "pwd" => "! ;4triou" ));
if (!$conn)
{
    $errors = sqlsrv_errors();
    echo( $errors[0]["message"]);
}
sqlsrv_close( $conn );

$conn = toConnect(array( "UID" => "test_password2", "pwd" => "!}} ;4triou" ));
if (!$conn)
{
    $errors = sqlsrv_errors();
    echo( $errors[0]["message"]);
}
sqlsrv_close( $conn );

$conn = toConnect(array( "UID" => "test_password3", "pwd" => "! ;4triou}}" ));
if (!$conn)
{
    $errors = sqlsrv_errors();
    echo( $errors[0]["message"]);
}
sqlsrv_close( $conn );

$conn = toConnect(array( "UID" => "test_password3", "pwd" => "! ;4triou}" ));
if ($conn)
{
    echo( "Shouldn't have connected" );
}
$errors = sqlsrv_errors();
echo $errors[0]["message"];
sqlsrv_close( $conn );

print "Test successful";
?> 
--EXPECTREGEX--
An unescaped right brace \(}\) was found in either the user name or password.  All right braces must be escaped with another right brace \(}}\)\.
Warning: sqlsrv_close\(\) expects parameter 1 to be resource, boolean given in .+(\/|\\)test_non_alpha_password\.php on line 45
Test successful
