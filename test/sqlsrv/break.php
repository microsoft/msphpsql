<?php
// Set SQL server + user + password
	$serverName = getenv('MSSQL_SERVERNAME') ?: "localhost";
	$username   = getenv('MSSQL_USERNAME') ?:   "sa";
	$password   = getenv('MSSQL_PASSWORD') ?:   "<YourStrong!Passw0rd>";

// Generate unique DB name, example: php_20160817_1471475608267
$databaseName = "php_" . date( "Ymd" ) . "_" . round( microtime( true )*1000 );

// Generic table name example: php_20160817_1471475608267.dbo.php_firefly
$tableName1 = $databaseName.".dbo.php_firefly1";
$tableName2 = $databaseName.".dbo.php_firefly2";

function GenerateDatabase( $serverName, $username, $password, $databaseName, $tableName1, $tableName2 )
{
    $connectionInfo = array( "UID"=>"$username", "PWD"=>"$password" );

    $conn = sqlsrv_connect( $serverName, $connectionInfo );
    if ( $conn === false )
    {
        echo "Failure in GenerateDatabase $serverName $username $password\n";
        die ( print_r( sqlsrv_errors() ) );
    }

    // CREATE database
    $stmt0 = sqlsrv_query( $conn, "CREATE DATABASE $databaseName" );

    // Create table
    $sql = "CREATE TABLE $tableName1 ( c1 INT, c2 VARCHAR(40) )";
    $stmt = sqlsrv_query( $conn, $sql );

    // Insert data
    $sql = "INSERT INTO $tableName1 VALUES ( ?, ? )";
    for( $t = 100; $t < 116; $t++ ) 
    {
        $ts = substr( sha1( $t ),0,5 );
        $params = array( $t,$ts );
        $stmt = sqlsrv_prepare( $conn, $sql, $params );
        sqlsrv_execute( $stmt );
    }

    // Create table
    $sql = "CREATE TABLE $tableName2 ( c1 INT, c2 VARCHAR(40) )";
    $stmt = sqlsrv_query( $conn, $sql );

    // Insert data
    $sql = "INSERT INTO $tableName2 VALUES ( ?, ? )";
    for( $t = 200; $t < 209; $t++ ) 
    {
        $ts = substr( sha1( $t ),0,5 );
        $params = array( $t,$ts );
        $stmt = sqlsrv_prepare( $conn, $sql, $params );
        sqlsrv_execute( $stmt );
    }

    sqlsrv_close( $conn );
}
 
// Break connection by getting the session ID and killing it
function BreakConnection( $conn, $conn_break )
{
    $stmt1 = sqlsrv_query( $conn, "SELECT @@SPID" );
    if ( sqlsrv_fetch( $stmt1 ) )
    {
        $spid=sqlsrv_get_field( $stmt1, 0 );
    }

    sleep(1);

    $stmt0 = sqlsrv_prepare( $conn_break, "KILL ".$spid );
    sqlsrv_execute( $stmt0 );
}

GenerateDatabase( $serverName, $username, $password, $databaseName, $tableName1, $tableName2 );

?>
