--TEST--
Test for fetch_object
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

class foo
{
    public $stuff = "stuff";

    private $id = -1;
    
    private $id_foo = -2;
    
    function __construct( $a, $b )
    {
        echo "Creating a foo with params $a & $b\n";
    }

    function do_foo()
    {
        echo "Doing foo. $this->id_foo $this->id $this->stuff\n"; 
        $this->id_foo = 4;
    }
}

class foo_noargs
{
    public $stuff = "stuff";

    private $id = -1;
    
    private $id_foo = -2;
    
    function do_foo()
    {
        echo "Doing foo. $this->id_foo $this->id $this->stuff\n"; 
        $this->id_foo = 4;
    }
} // end class foo_noargs

sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
require_once('MsCommon.inc');

$conn = AE\connect();
$tableName = 'test_params';
$columns = array(new AE\ColumnMeta('tinyint', 'id'),
                 new AE\ColumnMeta('char(10)', 'name'),
                 new AE\ColumnMeta('float', 'double'),
                 new AE\ColumnMeta('varchar(max)', 'stuff'));
AE\createTable($conn, $tableName, $columns);

$f1 = 1;
$f2 = "testtestte";
$f3 = 12.0;
$f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );

$stmt = sqlsrv_prepare( $conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 )); 
if( !$stmt ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_prepare failed." );        
}

$success = sqlsrv_execute( $stmt );
if( !$success ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_execute failed." );        
}
while( $success = sqlsrv_send_stream_data( $stmt )) {
}
if( !is_null( $success )) {
    sqlsrv_cancel( $stmt );
    sqlsrv_free_stmt( $stmt );
    die( "sqlsrv_send_stream_data failed." );
}

$f1 = 2;
$f3 = 13.0;
$f4 = fopen( "data://text/plain,This%20is%20some%20more%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
$stmt2 = sqlsrv_prepare( $conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
$success = sqlsrv_execute( $stmt2 );
if( !$success ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_execute failed." );        
}
while( $success = sqlsrv_send_stream_data( $stmt2 )) {
}
if( !is_null( $success )) {
    sqlsrv_cancel( $stmt2 );
    sqlsrv_free_stmt( $stmt2 );
    die( "sqlsrv_send_stream_data failed." );
}

$f1 = 3;
$f3 = 14.0;
$f4 = fopen( "data://text/plain,This%20is%20some%20more%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
$stmt3 = sqlsrv_prepare( $conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
$success = sqlsrv_execute( $stmt3 );
if( !$success ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_execute failed." );        
}
while( $success = sqlsrv_send_stream_data( $stmt3 )) {
}
if( !is_null( $success )) {
    sqlsrv_cancel( $stmt3 );
    sqlsrv_free_stmt( $stmt3 );
    die( "sqlsrv_send_stream_data failed." );
}

$f1 = 4;
$f3 = 15.0;
$f4 = fopen( "data://text/plain,This%20is%20some%20more%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
$stmt4 = sqlsrv_prepare( $conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
$success = sqlsrv_execute( $stmt4 );
if( !$success ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_execute failed." );        
}
while( $success = sqlsrv_send_stream_data( $stmt4 )) {
}
if( !is_null( $success )) {
    sqlsrv_cancel( $stmt4 );
    sqlsrv_free_stmt( $stmt4 );
    die( "sqlsrv_send_stream_data failed." );
}

$f1 = 5;
$f3 = 16.0;
$f4 = fopen( "data://text/plain,This%20is%20some%20more%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
$stmt5 = sqlsrv_prepare( $conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
$success = sqlsrv_execute( $stmt5 );
if( !$success ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_execute failed." );        
}
while( $success = sqlsrv_send_stream_data( $stmt5 )) {
}
if( !is_null( $success )) {
    sqlsrv_cancel( $stmt5 );
    sqlsrv_free_stmt( $stmt5 );
    die( "sqlsrv_send_stream_data failed." );
}

sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_prepare( $conn, "SELECT id, [double], name, stuff FROM $tableName" );
$success = sqlsrv_execute( $stmt );
if( !$success ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_execute failed." );        
}

echo "Fetch a stdClass object (1)\n";
$obj = sqlsrv_fetch_object( $stmt );
if( $obj === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( $obj );

echo "Fetch a foo_noargs object (2)\n";
$obj = sqlsrv_fetch_object( $stmt, "foo_noargs" );
if( $obj === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$obj->do_foo();
print_r( $obj );

echo "Fetch a foo object (with constructor args) (3)\n";
$obj = sqlsrv_fetch_object( $stmt, "foo", array( 2, 1 ) );
if( $obj === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$obj->do_foo();
print_r( $obj );

echo "Just create a normal foo in the script\n";
$next_obj = new foo( 1, 2 );
print_r( $next_obj );

// this case prints out warnings for 7.0.x but not passing enough argument 
// results in a fatal error for 7.1.x
echo "With no constructor arguments (4)\n";
try {
    $obj = sqlsrv_fetch_object( $stmt, "foo" );
    if( $obj === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $obj->do_foo();
    print_r( $obj );
}
catch (Error $e)
{
    echo "Caught error: " . $e->getMessage() . "\n";
}

// the case with args to an object that doesn't take them
echo "Non args constructor with args (5)\n";
$obj = sqlsrv_fetch_object( $stmt, "foo_noargs", array( 1, 2 ));
if( $obj === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
if( is_null( $obj )) {
    echo "Done fetching objects.\n";        
}
else {
    $obj->do_foo();
    print_r( $obj );
}

// the end of result set case
echo "At the end of the result set (6)\n";
$obj = sqlsrv_fetch_object( $stmt, "foo" );
if( $obj === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
if( is_null( $obj )) {
    echo "Done fetching objects.\n";        
}
else {
    $obj->do_foo();
    print_r( $obj );
}

// past the end of result set case
echo "Past the end of the result set (7)\n";
$obj = sqlsrv_fetch_object( $stmt, "foo" );
if( $obj === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
if( is_null( $obj )) {
    echo "Done fetching objects.\n";        
}
else {
    $obj->do_foo();
    print_r( $obj );
}

dropTable($conn, $tableName);

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );

?>

--EXPECTREGEX--
Fetch a stdClass object \(1\)
stdClass Object
\(
    \[id\] => 1
    \[double\] => 12
    \[name\] => testtestte
    \[stuff\] => This is some text meant to test binding parameters to streams
\)
Fetch a foo_noargs object \(2\)
Doing foo\. -2 2 This is some more text meant to test binding parameters to streams
foo_noargs Object
\(
    \[stuff\] => This is some more text meant to test binding parameters to streams
    \[id:foo_noargs:private\] => 2
    \[id_foo:foo_noargs:private\] => 4
    \[double\] => 13
    \[name\] => testtestte
\)
Fetch a foo object \(with constructor args\) \(3\)
Creating a foo with params 2 & 1
Doing foo\. -2 3 This is some more text meant to test binding parameters to streams
foo Object
\(
    \[stuff\] => This is some more text meant to test binding parameters to streams
    \[id:foo:private\] => 3
    \[id_foo:foo:private\] => 4
    \[double\] => 14
    \[name\] => testtestte
\)
Just create a normal foo in the script
Creating a foo with params 1 \& 2
foo Object
\(
    \[stuff\] => stuff
    \[id:foo:private\] => -1
    \[id_foo:foo:private\] => -2
\)
With no constructor arguments \(4\)
(Caught error: Too few arguments to function foo::__construct\(\), 0 passed and exactly 2 expected|
Warning: Missing argument 1 for foo::__construct\(\).+sqlsrv_fetch_object_2\.php.+Warning: Missing argument 2 for foo::__construct\(\).+sqlsrv_fetch_object_2\.php.+Notice: Undefined variable: a in.+sqlsrv_fetch_object_2\.php.+Notice: Undefined variable: b in.+sqlsrv_fetch_object_2\.php.+Creating a foo with params  \&.+Doing foo\. -2 4 This is some more text meant to test binding parameters to streams.+foo Object.+\(.+\[stuff\] => This is some more text meant to test binding parameters to streams.+\[id:foo:private\] => 4.+\[id_foo:foo:private\] => 4.+\[double\] => 15.+\[name\] => testtestte.+\))
Non args constructor with args \(5\)
Doing foo. -2 5 This is some more text meant to test binding parameters to streams
foo_noargs Object
\(
    \[stuff\] => This is some more text meant to test binding parameters to streams
    \[id:foo_noargs:private\] => 5
    \[id_foo:foo_noargs:private\] => 4
    \[double\] => 16
    \[name\] => testtestte
\)
At the end of the result set \(6\)
Done fetching objects\.
Past the end of the result set \(7\)
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -22
            \[code\] => -22
            \[2\] => There are no more rows in the active result set\.  Since this result set is not scrollable\, no more data may be retrieved\.
            \[message\] => There are no more rows in the active result set\.  Since this result set is not scrollable\, no more data may be retrieved\.
        \)

\)
