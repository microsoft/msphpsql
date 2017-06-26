--TEST--
Test setting and getting various statement attributes.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
function set_stmt_option($conn, $arr)
{
    try {
    
        $stmt = $conn->prepare( "Select * from temptb", $arr );
        return $stmt;
    }
    
    catch( PDOException $e)
    {
        echo $e->getMessage() . "\n\n";
        return NULL;
    } 
}
 
function set_stmt_attr($conn, $attr, $val)
{
    $stmt = NULL;
    try 
    {
        echo "Set Attribute: " . $attr . "\n";
        $stmt = $conn->prepare( "Select * from temptb");
    }
    catch( PDOException $e)
    {
        echo $e->getMessage() . "\n\n";
        return NULL;
    }
        
    try {
        $res = $stmt->setAttribute(constant($attr), $val);
        var_dump($res);
        echo "\n\n";
    }
     
    catch( PDOException $e)
    {
        echo $e->getMessage() . "\n\n";
    }
    return $stmt;
}

function get_stmt_attr($stmt, $attr)
{
    try 
    {
        echo "Get Attribute: " . $attr. "\n";
        $res = $stmt->getAttribute(constant($attr));
        var_dump($res);
        echo "\n";
    }
    
    catch( PDOException $e)
    {
        echo $e->getMessage() . "\n\n";
    } 
}
  
// valid
 function Test1($conn)
{
    echo "Test1 - Set stmt option: SQLSRV_ATTR_ENCODING, ATTR_CURSOR, SQLSRV_ATTR_QUERY_TIMEOUT \n";
    set_stmt_option($conn, array(PDO::SQLSRV_ATTR_ENCODING => 3, PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 44));
    echo "Test Successful\n\n";
}
 
// invalid
function Test2($conn)
{
    echo "Test2 - Set stmt option: 'QueryTimeout' \n";
    set_stmt_option($conn, array("QueryTimeout" => 44 ));
}
 
// valid attributes
function Test3($conn)
{
    echo "Test3 \n";
    $attr = "PDO::ATTR_CURSOR";
    $stmt = set_stmt_attr($conn, $attr, 1);
    if($stmt)
        get_stmt_attr($stmt, $attr);
    else
        echo "Test3: stmt was null";
}
 
// not supported attribute
function Test4($conn)
{
    echo "Test4 \n";
    $attr = "PDO::ATTR_SERVER_VERSION";
    $stmt = set_stmt_attr($conn, $attr, "whatever");
    get_stmt_attr($stmt, $attr);
}
 
// not supported attribute value
function Test5($conn)
{
    echo "Test5 \n";
    $attr = "PDO::ATTR_CURSOR";
    $stmt = set_stmt_attr($conn, $attr, 3);
    get_stmt_attr($stmt, $attr);
}
 
// valid GET/SET attribute and set option
function Test6($conn)
{
    echo "Test6 - Set stmt option: SQLSRV_ATTR_ENCODING \n";
    set_stmt_option($conn, array(PDO::SQLSRV_ATTR_ENCODING => 3));
    
    $attr = "PDO::SQLSRV_ATTR_QUERY_TIMEOUT";
    $stmt = set_stmt_attr($conn, $attr, 45);
    get_stmt_attr($stmt, $attr);
    
}
 
 
try 
{   
    include("MsSetup.inc");
   
    $conn = new PDO( "sqlsrv:Server=$server; Database = $databaseName ", $uid, $pwd);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $conn->exec("IF OBJECT_ID('temptb', 'U') IS NOT NULL DROP TABLE temptb");
    $conn->exec("CREATE TABLE temptb(id INT NOT NULL PRIMARY KEY, val VARCHAR(10)) ");
   
    test1($conn);
    test2($conn);
    test3($conn);
    test4($conn);
    test5($conn);
    test6($conn);
 
}

catch( PDOException $e ) {

    var_dump( $e );
    exit;
}

?> 

--EXPECTREGEX--
Test1 - Set stmt option: SQLSRV_ATTR_ENCODING, ATTR_CURSOR, SQLSRV_ATTR_QUERY_TIMEOUT 
Test Successful

Test2 - Set stmt option: 'QueryTimeout' 
SQLSTATE\[IMSSP\]: An invalid statement option was specified.

Test3 
Set Attribute: PDO::ATTR_CURSOR
SQLSTATE\[IMSSP\]: The PDO::ATTR_CURSOR and PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE attributes may only be set in the \$driver_options array of PDO::prepare.

Get Attribute: PDO::ATTR_CURSOR
int\(0\)

Test4 
Set Attribute: PDO::ATTR_SERVER_VERSION
(SQLSTATE\[IMSSP\]: An invalid attribute was designated on the PDOStatement object.)|(SQLSTATE\[IM001\]: Driver does not support this function: driver doesn't support getting that attribute)

Get Attribute: PDO::ATTR_SERVER_VERSION
SQLSTATE\[IMSSP\]: An invalid attribute was designated on the PDOStatement object.

Test5 
Set Attribute: PDO::ATTR_CURSOR
SQLSTATE\[IMSSP\]: The PDO::ATTR_CURSOR and PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE attributes may only be set in the \$driver_options array of PDO::prepare.

Get Attribute: PDO::ATTR_CURSOR
int\(0\)

Test6 - Set stmt option: SQLSRV_ATTR_ENCODING 
Set Attribute: PDO::SQLSRV_ATTR_QUERY_TIMEOUT
bool\(true\)


Get Attribute: PDO::SQLSRV_ATTR_QUERY_TIMEOUT
int\(45\)