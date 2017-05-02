--TEST--
Verify the Binary and Char encoding output when binding output string with SQLSTYPE option with different size.
--DESCRIPTION--
Tests different sizes of output string which may cause ODBC to return trunc error info.
With unixODBC 2.3.4, when connection pooling is enabled, error information maybe returned differently
than older versions (or with pooling disabled).
The NVARCHAR(1) section would cause an ODBC call to return an errorinfo to the driver causing the statement to fail.
With unixODBC 2.3.4 + pooling the statement executes without error.
--FILE--

<?php
require_once("MsCommon.inc");

$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$bindtable = "#BindStringTest";
$sproc = "#uspPerson";

// Create table
$stmt = sqlsrv_query( $conn, "CREATE TABLE $bindtable (PersonID int, Name nvarchar(50))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "INSERT INTO $bindtable (PersonID, Name) VALUES (10, N'Miller')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "INSERT INTO $bindtable (PersonID, Name) VALUES (11, N'JSmith')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}           

$tsql_createSP = "CREATE PROCEDURE $sproc
    @id int, @return nvarchar(50) OUTPUT
    AS
    BEGIN
    SET NOCOUNT ON;
    SET @return = (SELECT Name FROM $bindtable WHERE PersonID = @id)
    END";
    
$stmt = sqlsrv_query( $conn, $tsql_createSP);
if( $stmt === false )
{
    echo "Error in executing statement 2.\n";
    die( print_r( sqlsrv_errors(), true));
}

$tsql_callSP = "{call $sproc( ? , ?)}";


//***********************************************************************************************

echo "NVARCHAR(32)\n";
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NVARCHAR(32)
            ));

if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    print_r( sqlsrv_errors(), true);
}

$expectedLength = 6;
$expectedValue = "Miller";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NVARCHAR(32)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    print_r( sqlsrv_errors(), true);
}
 
$expectedLength = 12;
$expectedValue = "M\0i\0l\0l\0e\0r\0";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

//***********************************************************************************************
echo "\n\n";
echo "NVARCHAR(50)\n";
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NVARCHAR(50)
            ));

if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    print_r( sqlsrv_errors(), true);
}

$expectedLength = 6;
$expectedValue = "Miller";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );


echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NVARCHAR(50)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    print_r( sqlsrv_errors(), true);
}
     
$expectedLength = 12;
$expectedValue = "M\0i\0l\0l\0e\0r\0";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

//***********************************************************************************************
echo "\n\n";
echo "NVARCHAR(1)\n";
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";


$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NVARCHAR(1)
            ));

// with unixODBC 2.3.4 connection pooling the statement may not fail.
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    echo "Statement should fail\n";
}

$expectedLength = 1;
$expectedValue = "M";
$actualValue = $return;
$actualLength = strlen($return);
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NVARCHAR(1)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    echo "Statement should fail\n";
}
 
$expectedLength = 2;
$expectedValue = "M\0";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

//***********************************************************************************************
echo "\n\n";
echo "NCHAR(32)\n";
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NCHAR(32)
            ));

if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    print_r( sqlsrv_errors(), true);
}

$expectedLength = 32;
$expectedValue = "Miller                          ";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NCHAR(32)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    print_r( sqlsrv_errors(), true);
}
     
$expectedLength = 64;
$expectedValue = "M\0i\0l\0l\0e\0r\0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue ); 

//***********************************************************************************************
echo "\n\n";
echo "NCHAR(0)\n";
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NCHAR(0)
            ));

if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    echo "Statement should fail\n";
}

$expectedLength = 0;
$expectedValue = "";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue ); 

echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NCHAR(0)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    echo "Statement should fail\n";
}

$expectedLength = 0;
$expectedValue = "";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue ); 

//***********************************************************************************************
echo "\n\n";
echo "NCHAR(50)\n"; 
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NCHAR(50)
            ));

if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    print_r( sqlsrv_errors(), true);
}

$expectedLength = 50;
$expectedValue = "Miller                                            ";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue ); 

echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NCHAR(50)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    print_r( sqlsrv_errors(), true);
}
 
$expectedLength = 100;
$expectedValue = "M\0i\0l\0l\0e\0r\0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0 \0";
$actualLength = strlen($return);
$actualValue = $return;
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue ); 

//***********************************************************************************************
// NCHAR 1: less than length of the returned value 
echo "\n\n";
echo "NCHAR(1)\n";
echo "---------Encoding char-----------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                   SQLSRV_SQLTYPE_NCHAR(1)
            ));

if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) === false)
{
    print_r( sqlsrv_errors(), true);
}

$expectedLength = 1;
$expectedValue = "M";
$actualValue = $return;
$actualLength = strlen($return);
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

echo "---------Encoding binary---------\n";
$id = 10;
$return = "";
$params = array( 
    array($id, SQLSRV_PARAM_IN),
    array(&$return, SQLSRV_PARAM_OUT,
                    SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),   
                   SQLSRV_SQLTYPE_NCHAR(1)
            ));
if( $stmt = sqlsrv_query($conn, $tsql_callSP, $params) == false)
{
    print_r( sqlsrv_errors(), true);
}
 
$expectedLength = 2;
$expectedValue = "M\0";
$actualValue = $return;
$actualLength = strlen($return);
compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue );

sqlsrv_close($conn);

$status = true;

/**
*  Compares actual output to expected one
*  @param $expectedLength The length of the expected value
*  @param $expectedValue The expected value
*  @param $actualLength The length of the actual value
*  @param $actualValue The actual value
*/
function compareResults ( $expectedLength, $expectedValue, $actualLength, $actualValue )
{
    $match = false;
    if ( $expectedLength == $actualLength) 
    {
        if ( strncmp ( $actualValue, $expectedValue, $expectedLength ) == 0 )
        {
            $match = true;
        }
    } 
    if ( !$match )
    {
        echo "The actual result is different from the expected one \n";
    }
    else
    {
        echo "The actual result is the same as the expected one \n";
    }
}
?>
--EXPECT--
NVARCHAR(32)
---------Encoding char-----------
The actual result is the same as the expected one 
---------Encoding binary---------
The actual result is the same as the expected one 


NVARCHAR(50)
---------Encoding char-----------
The actual result is the same as the expected one 
---------Encoding binary---------
The actual result is the same as the expected one 


NVARCHAR(1)
---------Encoding char-----------
Statement should fail
The actual result is the same as the expected one 
---------Encoding binary---------
Statement should fail
The actual result is the same as the expected one 


NCHAR(32)
---------Encoding char-----------
The actual result is the same as the expected one 
---------Encoding binary---------
The actual result is the same as the expected one 


NCHAR(0)
---------Encoding char-----------
Statement should fail
The actual result is the same as the expected one 
---------Encoding binary---------
Statement should fail
The actual result is the same as the expected one 


NCHAR(50)
---------Encoding char-----------
The actual result is the same as the expected one 
---------Encoding binary---------
The actual result is the same as the expected one 


NCHAR(1)
---------Encoding char-----------
The actual result is the same as the expected one 
---------Encoding binary---------
The actual result is the same as the expected one
