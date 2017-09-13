--TEST--
Test new connection keyword ColumnEncryption
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );  
    require( 'MsSetup.inc' );
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>'Enabled');
    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Failed to connect.\n";
        print_r( sqlsrv_errors() );
    }
    else
    {
        echo "Connected successfully with ColumnEncryption enabled.\n";
        sqlsrv_close( $conn );
    }
   
    ////////////////////////////////////////
    $connectionInfo['ColumnEncryption']='false';
    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Failed to connect.\n";
        print_r( sqlsrv_errors() );
    }
    ////////////////////////////////////////
    $connectionInfo['ColumnEncryption']=true;
    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Failed to connect.\n";
        print_r( sqlsrv_errors() );
    }
    ////////////////////////////////////////
    $connectionInfo['ColumnEncryption']='Disabled';
    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Failed to connect.\n";
        print_r( sqlsrv_errors() );
    }
    else
    {
        echo "Connected successfully with ColumnEncryption disabled.\n";
        sqlsrv_close( $conn );
    }
       
    echo "Done\n";
?>
--EXPECTREGEX--
Connected successfully with ColumnEncryption enabled.
Failed to connect.
Array
\(
    \[0\] => Array
        \(
            \[0\] => 08001
            \[SQLSTATE\] => 08001
            \[1\] => 0
            \[code\] => 0
            \[2\] => .*\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ColumnEncryption'
            \[message\] => .*\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid value specified for connection string attribute 'ColumnEncryption'
        \)

\)
Failed to connect.
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -33
            \[code\] => -33
            \[2\] => Invalid value type for option ColumnEncryption was specified.  String type was expected.
            \[message\] => Invalid value type for option ColumnEncryption was specified.  String type was expected.
        \)

\)
Connected successfully with ColumnEncryption disabled.
Done