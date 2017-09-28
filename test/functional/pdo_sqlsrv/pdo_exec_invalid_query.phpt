--TEST--
direct execution of an invalid query
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require_once( "MsCommon.inc" );

    $conn = connect();
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
    
    $tbname = "table1";
    $conn->exec("IF OBJECT_ID('$tbname', 'U') IS NOT NULL DROP TABLE $tbname");
    
    // execute a query with typo (spelling error in CREATE)
    $conn->exec("CRETE TABLE $tbname (id INT NOT NULL PRIMARY KEY, val VARCHAR(10)) ");
    print_r( $conn->errorCode() );
    echo "\n";
    
    // execute a properly formatted query
    $conn->exec("CREATE TABLE $tbname (id INT NOT NULL PRIMARY KEY, val VARCHAR(10)) ");
    print_r( $conn->errorCode() );
    echo "\n";
    
    // drop table1 and free connections
    $conn->exec("DROP TABLE $tbname");
    unset( $conn );
?>
--EXPECT--
42000
00000