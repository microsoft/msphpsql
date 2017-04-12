--TEST--
Test PDO::__Construct with incorrectly formatted DSN or no Server specified in DSN
--SKIPIF--

--FILE--
<?php
  
require_once("autonomous_setup.php");

/*----------Connection option cases that raises errors----------*/
//dsn with 2 consecutive semicolons
try 
{   
    $conn = new PDO( "sqlsrv:Server = $serverName;;", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}

//dsn with double right curly braces
try 
{   
    $conn = new PDO( "sqlsrv:Server =$serverName; Database = {tempdb}}", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}

//dsn with double right curly braces and semicolon
try 
{   
    $conn = new PDO( "sqlsrv:Server =$serverName; Database = {tempdb}};", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}

//dsn with right curly braces and other symbol
try 
{   
    $conn = new PDO( "sqlsrv:Server =$serverName; Database = {tempdb}?", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}

//dsn with no equal sign in one option
try 
{   
    $conn = new PDO( "sqlsrv:Server =$serverName; Database", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}

//dsn with no keys
try 
{   
    // Try to connect with no server specific
    @$conn = new PDO( "sqlsrv:", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}
// Try to connect with no server specified
try 
{   
    $database = "tempdb";
    @$conn = new PDO( "sqlsrv:Database = $database", $username, $password );
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}

echo "\n";
/*----------Connection option cases that is OK----------*/

try 
{   
    //dsn with curly braces
    $conn = new PDO( "sqlsrv:Server =$serverName; Database = {tempdb}", $username, $password );
    echo "value in curly braces OK\n";
    
    //dsn with curly braces and semicolon
    @$conn = new PDO( "sqlsrv:Server =$serverName; Database = {tempdb};", $username, $password );
    echo "value in curly braces followed by a semicolon OK\n";
    
    //dsn with curly braces and trailing spaces
    @$conn = new PDO( "sqlsrv:Server =$serverName; Database = {tempdb}    ", $username, $password );
    echo "value in curly braces followed by trailing spaces OK\n";
    
    //dsn with no value specified and ends with semicolon
    $conn = new PDO( "sqlsrv:Server =$serverName; Database = ;", $username, $password );
    echo "dsn with no value specified and ends with semicolon OK\n";
}
catch( PDOException $e ) {
    print_r( ($e->errorInfo)[2] );
}


?> 

--EXPECTREGEX--

An extra semi-colon was encountered in the DSN string at character \(byte-count\) position '[0-9]+' \.
An unescaped right brace \(\}\) was found in the DSN string for keyword  'Database'\.  All right braces must be escaped with another right brace \(\}\}\)\.
An expected right brace \(\}\) was not found in the DSN string for the value of the keyword 'Database'\.
An invalid value was specified for the keyword 'Database' in the DSN string\.
The DSN string ended unexpectedly\.
An invalid DSN string was specified\.
Server keyword was not specified in the DSN string\.

value in curly braces OK
value in curly braces followed by a semicolon OK
value in curly braces followed by trailing spaces OK
dsn with no value specified and ends with semicolon OK