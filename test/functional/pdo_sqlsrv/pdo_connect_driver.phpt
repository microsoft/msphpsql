--TEST--
Test new connection keyword Driver with valid and invalid values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( 'MsSetup.inc' );

try
{
    $conn = new PDO( "sqlsrv:server = $server", $uid, $pwd );
    $msodbcsql_ver = $conn->getAttribute( PDO::ATTR_CLIENT_VERSION )['DriverVer'];
    $msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
}
catch( PDOException $e )
{
    echo "Failed to connect\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$conn = null;

// start test
test_valid_values();
test_invalid_values();
test_encrypted_with_odbc();
test_wrong_odbc();
echo "Done";
// end test

///////////////////////////
function connect_verify_output( $connectionOptions, $expected = '' )
{
    global $server, $uid, $pwd;
    
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionOptions", $uid, $pwd );
    }
    catch( PDOException $e )
    {
        if ( strpos($e->getMessage(), $expected ) === false )
        {
            print_r( $e->getMessage() );
            echo "\n";
        }
    }
}

function test_valid_values()
{
    global $msodbcsql_maj;
    
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
    $connectionOptions = "Driver = $value";
    connect_verify_output( $connectionOptions );
    
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
    
    $connectionOptions = "Driver = $value";
    connect_verify_output( $connectionOptions );
}

function test_invalid_values()
{
    // test invalid value
    $value = "{SQL Server Native Client 11.0}";
    $connectionOptions = "Driver = $value";
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $connectionOptions, $expected );
    
    $value = "SQL Server Native Client 11.0";
    $connectionOptions = "Driver = $value";
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $connectionOptions, $expected );
    
    $value = "ODBC Driver 00 for SQL Server";
    $connectionOptions = "Driver = $value";
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $connectionOptions, $expected );
    
    $value = 123;
    $connectionOptions = "Driver = $value";
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $connectionOptions, $expected );
    
    $value = false;
    $connectionOptions = "Driver = $value";
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $connectionOptions, $expected );
}

function test_encrypted_with_odbc() 
{
    global $msodbcsql_maj;

    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions = "Driver = $value; ColumnEncryption = Enabled;"; 
    $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
    
    connect_verify_output( $connectionOptions, $expected );
}

function test_wrong_odbc()
{
    global $msodbcsql_maj, $server, $uid, $pwd;
    
    $value = "ODBC Driver 11 for SQL Server";
    if ( $msodbcsql_maj == 17 || $msodbcsql_maj < 13 )
    {
        $value = "ODBC Driver 13 for SQL Server";
    }
    $connectionOptions = "Driver = $value;";
    
    try
    {
        $conn = new PDO( "sqlsrv:server = $server ; $connectionOptions", $uid, $pwd );

        echo "Should have caused an exception!\n";
    }
    catch( PDOException $e )
    {
        // do nothing here because this is expected
    }
}

?>
--EXPECT--
Done