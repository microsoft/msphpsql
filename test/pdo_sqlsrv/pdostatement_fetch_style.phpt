--TEST--
Test the PDOStatement::fetch() method with different fetch styles.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';

function fetch_both( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from ". $table1 );
    $result = $stmt->fetch(PDO::FETCH_BOTH);
    var_dump($result);
    $stmt->closeCursor();
}

function fetch_assoc( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from ". $table1 );
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
    $stmt->closeCursor();
}

function fetch_lazy( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from " . $table1 );
    $result = $stmt->fetch(PDO::FETCH_LAZY);
    var_dump($result);
    $stmt->closeCursor();
}

function fetch_obj( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from ". $table1 );
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    var_dump($result);
    $stmt->closeCursor();
}

function fetch_num( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from ". $table1 );
    $result = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($result);
    $stmt->closeCursor();

}

function fetch_bound( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from ". $table1 );   
    $stmt->bindColumn('IntCol', $IntCol);
    $stmt->bindColumn('CharCol', $CharCol);
    $stmt->bindColumn('NCharCol', $NCharCol);
    $stmt->bindColumn('DateTimeCol', $DateTimeCol);
    $stmt->bindColumn('VarcharCol', $VarcharCol);
    $stmt->bindColumn('NVarCharCol', $NVarCharCol);
    $stmt->bindColumn('FloatCol', $FloatCol);
    $stmt->bindColumn('XmlCol', $XmlCol);
    $result = $stmt->fetch(PDO::FETCH_BOUND);
    if (!$result)
    {
        die("Error in FETCH_BOUND\n");
    }
    var_dump($IntCol);
    var_dump($CharCol);
    var_dump($NCharCol);
    var_dump($DateTimeCol);
    var_dump($VarcharCol);
    var_dump($NVarCharCol);
    var_dump($FloatCol);
    var_dump($XmlCol);
    $stmt->closeCursor();
}

function fetch_class( $conn )
{
    global $table1;
    global $table1_class;
    $stmt = $conn->query( "Select * from ". $table1 );   
    $stmt->setFetchMode(PDO::FETCH_CLASS, $table1_class);
    $result = $stmt->fetch(PDO::FETCH_CLASS);
    $result->dumpAll(); 
    $stmt->closeCursor();
}

function fetch_into( $conn )
{
    global $table1;
    global $table1_class;
    $stmt = $conn->query( "Select * from ". $table1 );   
    $obj = new $table1_class;
    $stmt->setFetchMode(PDO::FETCH_INTO, $obj);
    $result = $stmt->fetch(PDO::FETCH_INTO);
    $obj->dumpAll();    
    $stmt->closeCursor();
}

function fetch_invalid( $conn )
{
    global $table1;
    $stmt = $conn->query( "Select * from ". $table1 );   
    try
    {
        $result = $stmt->fetch(PDO::FETCH_UNKNOWN);
    }
    catch(PDOException $err)
    {
        print_r($err);
    }   
}

try 
{      
    $db = connect();
    create_and_insert_table1( $db );
    echo "Test_1 : FETCH_BOTH :\n";
    fetch_both($db);
    echo "Test_2 : FETCH_ASSOC :\n";
    fetch_assoc($db);
    echo "Test_3 : FETCH_LAZY :\n";
    fetch_lazy($db);
    echo "Test_4 : FETCH_OBJ :\n";
    fetch_obj($db);
    echo "Test_5 : FETCH_NUM :\n";
    fetch_num($db);
    echo "Test_6 : FETCH_BOUND :\n";
    fetch_bound($db);
    echo "Test_7 : FETCH_CLASS :\n";
    fetch_class($db);
    echo "Test_8 : FETCH_INTO :\n";
    fetch_into($db);
    echo "Test_9 : FETCH_INVALID :\n";
    fetch_invalid($db);
}
catch( PDOException $e ) {
    var_dump( $e );
    exit;
}


?>  

--EXPECTF--
Test_1 : FETCH_BOTH :
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
Test_2 : FETCH_ASSOC :
array(8) {
  ["IntCol"]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL1"
  ["FloatCol"]=>
  string(7) "111.111"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_3 : FETCH_LAZY :
object(PDORow)#%x (%x) {
  ["queryString"]=>
  string(25) "Select * from PDO_Types_1"
  ["IntCol"]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL1"
  ["FloatCol"]=>
  string(7) "111.111"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_4 : FETCH_OBJ :
object(stdClass)#%x (%x) {
  ["IntCol"]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL1"
  ["FloatCol"]=>
  string(7) "111.111"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_5 : FETCH_NUM :
array(8) {
  [0]=>
  string(1) "1"
  [1]=>
  string(10) "STRINGCOL1"
  [2]=>
  string(10) "STRINGCOL1"
  [3]=>
  string(23) "2000-11-11 11:11:11.110"
  [4]=>
  string(10) "STRINGCOL1"
  [5]=>
  string(10) "STRINGCOL1"
  [6]=>
  string(7) "111.111"
  [7]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_6 : FETCH_BOUND :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_7 : FETCH_CLASS :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_8 : FETCH_INTO :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_9 : FETCH_INVALID :

Fatal error: Uncaught Error: Undefined class constant 'FETCH_UNKNOWN' in %s:%x
Stack trace:
#0 %s: fetch_invalid(Object(PDO))
#1 {main}
  thrown in %s on line %x