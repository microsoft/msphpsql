--TEST--
Read, Update, Insert from a SQLSRV stream with buffered query
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require( 'connect.inc' );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( !$conn ) {
    var_dump( sqlsrv_errors() );
    die( "sqlsrv_connect failed." );
}

$query = "IF OBJECT_ID('PhpCustomerTable', 'U') IS NOT NULL DROP TABLE [PhpCustomerTable]";
$stmt = sqlsrv_prepare( $conn, $query, array(), array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );

$query = "CREATE TABLE [PhpCustomerTable] ([Id] int NOT NULL Identity (100,2) PRIMARY KEY, [Field2] text, [Field3] image, [Field4] ntext, [Field5] varbinary(max), [Field6] varchar(max), [Field7] nvarchar(max))";
$stmt = sqlsrv_prepare( $conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );

$query = "INSERT INTO [PhpCustomerTable] ([Field2], [Field3], [Field4], [Field5], [Field6], [Field7]) VALUES ('This is field 2.', 0x010203, 'This is field 4.', 0x040506, 'This is field 6.', 'This is field 7.' )";
$stmt = sqlsrv_prepare( $conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true));
}

sqlsrv_execute( $stmt );

$f2 = fopen('php://memory', 'a');
fwrite($f2, 'Update field 2.');
rewind($f2);
$f3 = fopen('php://memory', 'a');
fwrite($f3, 0x010204);
rewind($f3);
$f4 = fopen('php://memory', 'a');
fwrite($f4, 'Update field 4.');
rewind($f4);
$f5 = fopen('php://memory', 'a');
fwrite($f5, 0x040503);
rewind($f5);
$f6 = fopen('php://memory', 'a');
fwrite($f6, 'Update field 6.');
rewind($f6);
$f7 = fopen('php://memory', 'a');
fwrite($f7, 'Update field 7.');
rewind($f7);



$query = "UPDATE [PhpCustomerTable] SET [Field2]=? WHERE [Field7]='This is field 7.'";
$params = array( array( &$f2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_TEXT ));
$stmt = sqlsrv_prepare( $conn, $query, $params, array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );

$query = "UPDATE [PhpCustomerTable] SET [Field3]=? WHERE [Field7]='This is field 7.'";
$params = array( array( &$f3, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_BINARY ), SQLSRV_SQLTYPE_IMAGE ));
$stmt = sqlsrv_prepare( $conn, $query, $params, array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );

$query = "UPDATE [PhpCustomerTable] SET [Field4]=? WHERE [Field7]='This is field 7.'";
$params = array( array( &$f4, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR ), SQLSRV_SQLTYPE_NTEXT ));
$stmt = sqlsrv_prepare( $conn, $query, $params, array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true );
}

sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );

$query = "UPDATE [PhpCustomerTable] SET [Field5]=? WHERE [Field7]='This is field 7.'";
$params = array(array( &$f5, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_BINARY ), SQLSRV_SQLTYPE_VARBINARY( 'max' )));
$stmt = sqlsrv_prepare( $conn, $query, $params, array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));


if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );


$query = "UPDATE [PhpCustomerTable] SET [Field6]=? WHERE [Field7]='This is field 7.'";
$params = array( array( &$f6, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR ), SQLSRV_SQLTYPE_VARCHAR('MAX')));
$stmt = sqlsrv_prepare( $conn, $query, $params, array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true));
}

sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );

$query = "UPDATE [PhpCustomerTable] SET [Field7]=? WHERE [Field7]='This is field 7.'";
$params = array(array( &$f7, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR ), SQLSRV_SQLTYPE_NVARCHAR('MAX')));
$stmt = sqlsrv_prepare( $conn, $query, $params, array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if(!$stmt)
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );


$stmt = sqlsrv_query( $conn, "SELECT * FROM [PhpCustomerTable]", array(), array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));
if(!$stmt)
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}
sqlsrv_fetch( $stmt );

$field = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if(!$field)
{
	print( "Failed to get text field\n" );
} 
else
{
	$field = str_replace("\0","",$field);
	print("$field\n");
}

$field = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if(!$field)
{
	print( "Failed to get text field\n" );
} 
else
{
	print( "$field\n" );
}

$field = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if( !$field )
{
	print( "Failed to get image field\n" );
} 
else
{	
	print("$field\n");
}

$field = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if( !$field )
{
	print( "Failed to get ntext field\n" );
} 
else
{
	print( "$field\n" );
}

$field = sqlsrv_get_field( $stmt, 4, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if(!$field)
{
	print( "Failed to get varbinary(max) field\n" );
} 
else
{
	print( "$field\n" );
}

$field = sqlsrv_get_field( $stmt, 5, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if( !$field )
{
	print( "Failed to get varchar(max) field\n" );
} 
else
{
	print("$field\n");
}

$field = sqlsrv_get_field( $stmt, 6, SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR) );
if( !$field )
{
	print( "Failed to get nvarchar(max) field\n" );
} 
else
{
	print("$field\n");
}

$query = "IF OBJECT_ID('PhpCustomerTable', 'U') IS NOT NULL DROP TABLE [PhpCustomerTable]";
$stmt = sqlsrv_prepare( $conn, $query, array(), array( "Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED ));

if( !$stmt )
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true ));
}

sqlsrv_execute( $stmt );

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );

?>
--EXPECT--
100
Update field 2.
3636303532
Update field 4.
323633343237
Update field 6.
Update field 7.