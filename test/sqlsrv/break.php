<?php
// Set SQL server + user + password
$serverName = getenv( 'MSSQL_SERVERNAME' ) ?: "sql-2k14-sp1-1.galaxy.ad";
$username   = getenv( 'MSSQL_USERNAME' )   ?: "sa";
$password   = getenv( 'MSSQL_PASSWORD' )   ?: "Moonshine4me";

// Generate unique DB name, example: php_20160817_1471475608267
$databaseName = "php_" . date( "Ymd" ) . "_" . round( microtime( true )*1000 );

// Generic table name example: php_20160817_1471475608267.dbo.php_firefly
$tableName1 = $databaseName.".dbo.php_firefly1";
$tableName2 = $databaseName.".dbo.php_firefly2";

function GenerateDatabase( $serverName, $username, $password, $databaseName, $tableName1, $tableName2 )
{
    $connectionInfo = array( "uid"=>"$username", "pwd"=>"$password" );

    $conn = sqlsrv_connect( $serverName, $connectionInfo );
    if ( $conn === false )
    {
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
 
// Break connection by stopping (and not restarting) the remote SQL Server service
function StopMSSQLServer( $serverName )
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $restart_string = "$powershell ( get-service -ComputerName $serverName -Name mssqlserver ).Stop()";
    exec( $restart_string );
    $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    
    // Wait until the service is fully stopped
    $count = 0;
    while ( substr_count( $servstring, "Stopped" ) != 1 )
    {
        $count++;
        if ( $count > 30 ) die ( "Could not stop service.\n" );
        sleep( 1 );
        $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    }
}

// Start the remote SQL Server service
function StartMSSQLServer( $serverName )
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    if ( substr_count( $servstring, "Running" ) != 1 )
    {
        $restart_string = "$powershell ( get-service -ComputerName $serverName -Name mssqlserver ).Start()";
        exec( $restart_string );
    }
    $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    
    // Wait until the service is fully started
    $count = 0;
    while ( substr_count( $servstring, "Running" ) != 1 )
    {
        $count++;
        if ( $count > 30 ) die ( "Could not start service.\n" );
        sleep( 1 );
        $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    }
}

// Break connection by restarting remote SQL Server service
function RestartMSSQLServer( $serverName )
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $restart_string = "$powershell ( get-service -ComputerName $serverName -Name mssqlserver ).Stop()";
    exec( $restart_string );
    $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    
    // Wait until the service is fully stopped
    $count = 0;
    while ( substr_count( $servstring, "Stopped" ) != 1 )
    {
        $count++;
        if ( $count > 30 ) die ( "Could not stop service.\n" );
        sleep( 1 );
        $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    }
    $restart_string = "$powershell ( get-service -ComputerName $serverName -Name mssqlserver ).Start()";
    exec( $restart_string );
    $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    
    // Wait until the service is fully started
    $count = 0;
    while ( substr_count( $servstring, "Running" ) != 1 )
    {
        $count++;
        if ( $count > 30 ) die ( "Could not start service.\n" );
        sleep( 1 );
        $servstring = shell_exec( "$powershell get-service -ComputerName $serverName -Name mssqlserver" );
    }
}

StartMSSQLServer( $serverName );
GenerateDatabase( $serverName, $username, $password, $databaseName, $tableName1, $tableName2 );

?>