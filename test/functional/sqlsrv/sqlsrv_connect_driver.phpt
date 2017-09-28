--TEST--
Test new connection keyword Driver with valid and invalid values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
require( 'MsSetup.inc' );

$connectionOptions = array("Database"=>$database, "UID"=>$userName, "PWD"=>$userPassword);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn === false)
{
    print_r(sqlsrv_errors());
}
$msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
$msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
sqlsrv_close($conn);

// start test
test_valid_values( $msodbcsql_maj, $server, $connectionOptions );
test_invalid_values( $msodbcsql_maj, $server, $connectionOptions );
test_encrypted_with_odbc( $msodbcsql_maj, $server, $connectionOptions );
test_wrong_odbc( $msodbcsql_maj, $server, $connectionOptions );
echo "Done";
// end test

///////////////////////////
function connect_verify_output( $server, $connectionOptions, $expected = '' )
{
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false)
    {    
        if( strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false )
        {
            print_r(sqlsrv_errors());
        }
    }
}

function test_valid_values( $msodbcsql_maj, $server, $connectionOptions )
{
    $value = "";
    // Test with {}
    switch ( $msodbcsql_maj )
    {
        case 17:
            $value = "{ODBC Driver 17 for SQL Server}";
            break;
        case 13:
            $value = "{ODBC Driver 13 for SQL Server}";
            break;
        case 12:
        case 11:
            $value = "{ODBC Driver 11 for SQL Server}";
            break;            
        default:
            $value = "invalid value";
    }
    $connectionOptions['Driver']=$value;
    connect_verify_output( $server, $connectionOptions );
    
    // Test without {}
    switch ( $msodbcsql_maj )
    {
        case 17:
            $value = "ODBC Driver 17 for SQL Server";
            break;
        case 13:
            $value = "ODBC Driver 13 for SQL Server";
            break;
        case 12:
        case 11:
            $value = "ODBC Driver 11 for SQL Server";
            break;            
        default:
            $value = "invalid value";
    }
    
    $connectionOptions['Driver']=$value;
    connect_verify_output( $server, $connectionOptions );
}

function test_invalid_values( $msodbcsql_maj, $server, $connectionOptions )
{
    // test invalid value
    $value = "{SQL Server Native Client 11.0}";
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $server, $connectionOptions, $expected );
    
    $value = "SQL Server Native Client 11.0";
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $server, $connectionOptions, $expected );
    
    $value = "ODBC Driver 00 for SQL Server";
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $server, $connectionOptions, $expected );
    
    $value = 123;
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value type for option Driver was specified.  String type was expected.";
    connect_verify_output( $server, $connectionOptions, $expected );
    
    $value = false;
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value type for option Driver was specified.  String type was expected.";
    connect_verify_output( $server, $connectionOptions, $expected );
}

function test_encrypted_with_odbc( $msodbcsql_maj, $server, $connectionOptions ) 
{
    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions['Driver']=$value;
    $connectionOptions['ColumnEncryption']='Enabled';
    
    $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
    
    connect_verify_output( $server, $connectionOptions, $expected );
    
    // TODO: the following block will change once ODBC 17 is officially released
    $value = "ODBC Driver 17 for SQL Server";
    $connectionOptions['Driver']=$value;
    $connectionOptions['ColumnEncryption']='Enabled';
    
    $success = "Successfully connected with column encryption.";
    $expected = "The specified ODBC Driver is not found.";
    $message = $success;
    
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false)
        $message = sqlsrv_errors($conn)[0]['message'];

    if ( $msodbcsql_maj == 17 )
    {
        // this indicates that OCBC 17 is the only available driver
        if ( strcmp( $message, $success ) )
            print_r( $message );
    }
    else
    {
        // OCBC 17 might or might not exist
        if ( strcmp( $message, $success ) )
        {
            if ( strpos( $message, $expected ) === false )
                print_r( $message );
        }        
    }
    
}

function test_wrong_odbc( $msodbcsql_maj, $server, $connectionOptions )
{
    // TODO: this will change once ODBC 17 is officially released
    $value = "ODBC Driver 17 for SQL Server";
    if ( $msodbcsql_maj == 17 || $msodbcsql_maj < 13 )
    {
        $value = "ODBC Driver 13 for SQL Server";
    }

    $connectionOptions['Driver']=$value;
    $expected = "The specified ODBC Driver is not found.";
    
    connect_verify_output( $server, $connectionOptions, $expected );
}

?>
--EXPECT--
Done