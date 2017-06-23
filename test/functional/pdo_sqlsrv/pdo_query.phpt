--TEST--
Test the PDO::query() method.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
 
  
function query_default( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from " . $table1 );
    $result = $stmt->fetch();
    var_dump($result);
}
 
function query_column( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from " . $table1, PDO::FETCH_COLUMN, 2 );
    $result = $stmt->fetch();
    var_dump($result);
}
 
function query_class( $conn )
{
    global $table1;
    global $table1_class;
    $stmt = $conn->query( "Select * from " . $table1, PDO::FETCH_CLASS, $table1_class );
    $result = $stmt->fetch();
    $result->dumpAll();
}
  
function query_into( $conn )
{
    global $table1;
    global $table1_class;
    $obj = new $table1_class;
    $stmt = $conn->query( "Select * from " . $table1, PDO::FETCH_INTO, $obj );
    $result = $stmt->fetch();
    $result->dumpAll();
}
  
function query_empty_table( $conn )
{
    CreateTableEx($conn, 'emptyTable', "c1 INT, c2 INT");
    $stmt = $conn->query( "Select * from emptyTable");
    $result = $stmt->fetch();
    var_dump($result);
    DropTable($conn, 'emptyTable');
}

try 
{      
    $db = connect();
    echo "TEST_1 : query with default fetch style :\n";
    query_default($db); 

    echo "TEST_2 : query with FETCH_COLUMN :\n";
    query_column($db);

    echo "TEST_3 : query with FETCH_CLASS :\n";
    query_class($db);

    echo "TEST_4 : query with FETCH_INTO :\n";
    query_into($db);
    
    echo "TEST_5 : query an empty table :\n";
    query_empty_table($db);
}

catch( PDOException $e ) {
    var_dump( $e );
    exit;
}


?> 
--EXPECT--
TEST_1 : query with default fetch style :
array(16) {
  ["IntCol"]=>
  string(1) "1"
  [0]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
  [1]=>
  string(10) "STRINGCOL1"
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
  [2]=>
  string(10) "STRINGCOL1"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
  [3]=>
  string(23) "2000-11-11 11:11:11.110"
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
  [4]=>
  string(10) "STRINGCOL1"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL1"
  [5]=>
  string(10) "STRINGCOL1"
  ["FloatCol"]=>
  string(7) "111.111"
  [6]=>
  string(7) "111.111"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
  [7]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
TEST_2 : query with FETCH_COLUMN :
string(10) "STRINGCOL1"
TEST_3 : query with FETCH_CLASS :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
TEST_4 : query with FETCH_INTO :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
TEST_5 : query an empty table :
bool(false)