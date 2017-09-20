--TEST--
UTF-8 connection strings
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    $server = 'localhost';
    $databaseName = 'test';
    $uid = 'sa';
    $pwd = 'Sunshine4u';

    // test an invalid connection credentials
    $c = new PDO('sqlsrv:Server=' . $server . ';Database=' . $databaseName, $uid, $pwd);
    if( $c !== false ) 
    {
        die( "Should have failed to connect." );
    }

?>
--EXPECTREGEX--
  
Fatal error: Uncaught PDOException: SQLSTATE\[(28000|08001|HYT00)\]: .*\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\](\[SQL Server\])?(Named Pipes Provider: Could not open a connection to SQL Server \[2\]\. |Login timeout expired|Login failed for user 'sa'\.) in .+(\/|\\)pdo_utf8_conn\.php:[0-9]+
Stack trace:
#0 .+(\/|\\)pdo_utf8_conn\.php\([0-9]+\): PDO->__construct\('sqlsrv:Server=l\.\.\.', 'sa', 'Sunshine4u'\)
#1 {main}
  thrown in .+(\/|\\)pdo_utf8_conn\.php on line [0-9]+
