--TEST--
Test new connection keyword ColumnEncryption
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
require( 'MsSetup.inc' );

$connectionOptions = array("Database"=>$database,"UID"=>$userName, "PWD"=>$userPassword);
test_ColumnEncryption($server, $connectionOptions);
echo "Done";

function test_ColumnEncryption($server ,$connectionOptions){
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false)
    {
        print_r(sqlsrv_errors());
    }
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    $msodbcsql_maj = explode(".", $msodbcsql_ver)[0];

    // Only works for ODBC 17
    $connectionOptions['ColumnEncryption']='Enabled';
    $conn = sqlsrv_connect( $server, $connectionOptions );
    if( $conn === false )
    {
        if($msodbcsql_maj < 17){
            $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
            if( strcasecmp(sqlsrv_errors($conn)[0]['message'], $expected ) != 0 )
            {
                print_r(sqlsrv_errors());
            }
        }
        else
        {
            print_r(sqlsrv_errors());
        }
    }
    
    // Works for ODBC 17, ODBC 13
    $connectionOptions['ColumnEncryption']='Disabled';
    $conn = sqlsrv_connect( $server, $connectionOptions );
    if( $conn === false )
    {
        if($msodbcsql_maj < 13)
        {
            $expected_substr = "Invalid connection string attribute";
            if( strpos(sqlsrv_errors($conn)[0]['message'], $expected_substr ) === false )
            {
                print_r(sqlsrv_errors());
            }
        }
        else
        {
            print_r(sqlsrv_errors());
        }
    }
    else
    {
        sqlsrv_close($conn);
    }
    
    // should fail for all ODBC drivers
    $connectionOptions['ColumnEncryption']='false';
    $conn = sqlsrv_connect( $server, $connectionOptions );
    if( $conn === false )
    {
        $expected_substr = "Invalid value specified for connection string attribute 'ColumnEncryption'";
        if( strpos(sqlsrv_errors($conn)[0]['message'], $expected_substr ) === false )
        {
            print_r(sqlsrv_errors());
        }
    }
    
    // should fail for all ODBC drivers
    $connectionOptions['ColumnEncryption']=true;
    $conn = sqlsrv_connect( $server, $connectionOptions );
    if( $conn === false )
    {
        $expected_substr = "Invalid value type for option ColumnEncryption was specified.  String type was expected.";
        if( strpos(sqlsrv_errors($conn)[0]['message'], $expected_substr ) === false )
        {
            print_r(sqlsrv_errors());
        }
    }
    
    // should fail for all ODBC drivers
    $connectionOptions['ColumnEncryption']=false;
    $conn = sqlsrv_connect( $server, $connectionOptions );
    if( $conn === false )
    {
       $expected_substr = "Invalid value type for option ColumnEncryption was specified.  String type was expected.";
        if( strpos(sqlsrv_errors($conn)[0]['message'], $expected_substr ) === false )
        {
            print_r(sqlsrv_errors());
        }
    }
}
?>
--EXPECT--
Done