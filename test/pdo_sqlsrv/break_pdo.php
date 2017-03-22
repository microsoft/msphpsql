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
    $conn = new PDO( "sqlsrv:server = $serverName ; ", "$username", "$password" );
    if ( $conn === false )
    {
        die ( print_r( sqlsrv_errors() ) );
    }

    // CREATE database
    $conn->query( "CREATE DATABASE ". $databaseName );

    // Create table
    $sql = "CREATE TABLE $tableName1 ( c1 INT, c2 VARCHAR(40) )";
    $stmt = $conn->query( $sql );

    // Insert data
    $sql = "INSERT INTO $tableName1 VALUES ( ?, ? )";
    for( $t = 100; $t < 116; $t++ ) 
    {
        $stmt = $conn->prepare( $sql );
        $ts = substr( sha1( $t ),0,5 );
        $params = array( $t,$ts );
        $stmt->execute( $params );
    }

    // Create table
    $sql = "CREATE TABLE $tableName2 ( c1 INT, c2 VARCHAR(40) )";
    $stmt = $conn->query( $sql );

    // Insert data
    $sql = "INSERT INTO $tableName2 VALUES ( ?, ? )";
    for( $t = 200; $t < 209; $t++ ) 
    {
        $stmt = $conn->prepare( $sql );
        $ts = substr( sha1( $t ),0,5 );
        $params = array( $t,$ts );
        $stmt->execute( $params );
    }

    $conn = null;
}

// Break connection by getting the session ID and killing it
function BreakConnection( $conn, $conn_break )
{
    $stmt = $conn->query( "SELECT @@SPID" );
    $obj = $stmt->fetch( PDO::FETCH_NUM );
    $spid = $obj[0];

    sleep(1);
    $stmt1 = $conn_break->query( "KILL ".$spid );
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

GenerateDatabase( $serverName, $username, $password, $databaseName, $tableName1, $tableName2 );

?>