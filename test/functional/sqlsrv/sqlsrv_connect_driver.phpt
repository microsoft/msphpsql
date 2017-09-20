--TEST--
Test new connection keyword Driver with valid and invalid values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
require( 'MsSetup.inc' );

$connectionOptions = array("Database"=>$database,"UID"=>$userName, "PWD"=>$userPassword);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn === false)
{
    print_r(sqlsrv_errors());
}
$msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
$msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
sqlsrv_close($conn);

// start test
test_valid_values($msodbcsql_maj,$server ,$connectionOptions);
test_invalid_values($msodbcsql_maj,$server ,$connectionOptions);
echo "Done";
// end test

///////////////////////////
function connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions ,$expected = '' )
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

function test_valid_values( $msodbcsql_maj ,$server ,$connectionOptions){
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
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions );
    
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
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions );
}

function test_invalid_values($msodbcsql_maj ,$server ,$connectionOptions){
    // test invalid value
    $value = "{SQL Server Native Client 11.0}";
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions ,$expected );
    
    $value = "SQL Server Native Client 11.0";
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions ,$expected );
    
    $value = "ODBC Driver 00 for SQL Server";
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value $value was specified for Driver option.";
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions ,$expected );
    
    $value = 123;
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value type for option Driver was specified.  String type was expected.";
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions ,$expected );
    
    $value = false;
    $connectionOptions['Driver']=$value;
    $expected = "Invalid value type for option Driver was specified.  String type was expected.";
    connect_verify_output( $msodbcsql_maj ,$server ,$connectionOptions ,$expected );
}

?>
--EXPECT--
Done