<?php
// Set SQL server + user + password
$serverName = getenv('MSSQL_SERVERNAME') ?: "sql-2k14-sp2-1.galaxy.ad";
$username   = getenv('MSSQL_USERNAME')   ?: "sa";
$password   = getenv('MSSQL_PASSWORD')   ?: "Moonshine4me";

// Generate unique DB name, example: php_20160817_1471475608267
$databaseName = "php_" . date("Ymd") . "_" . round(microtime(true)*1000);

// Generic table name example: php_20160817_1471475608267.dbo.php_firefly
$tableName1 = $databaseName.".dbo.php_firefly1";
$tableName2 = $databaseName.".dbo.php_firefly2";

$connectionInfo = array( "Database"=>"$databaseName", "username"=>"$username", "password"=>"$password" );
$conn = sqlsrv_connect( $serverName, $connectionInfo );

// CREATE database
$stmt0 = sqlsrv_query($conn, "CREATE DATABASE $databaseName");

// Create table
$sql = "CREATE TABLE $tableName1 (c1 INT, c2 VARCHAR(40))";
$stmt = sqlsrv_query($conn, $sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName1 VALUES (?,?)";
for($t=100; $t<115; $t++) {
	$stmt = sqlsrv_prepare($conn, $sql);
	$ts = substr(sha1($t),0,5);
	$params = array($t,$ts);
	sqlsrv_execute($stmt, $params);
}

// Create table
$sql = "CREATE TABLE $tableName2 (c1 INT, c2 VARCHAR(40))";
$stmt = sqlsrv_query($conn, $sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName2 VALUES (?,?)";
for($t=200; $t<208; $t++) {
	$stmt = sqlsrv_prepare($conn, $sql);
	$ts = substr(sha1($t),0,5);
	$params = array($t,$ts);
	sqlsrv_execute($stmt, $params);
}

sqlsrv_close( $conn );

function RestartConn($serverName)
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $restart_string = "$powershell (get-service -ComputerName $serverName -Name mssqlserver).Stop()";
    exec( $restart_string );
    $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    
    // Wait until the service is fully stopped
    while (substr_count($servstring, "Stopped") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    }
    $restart_string = "$powershell (get-service -ComputerName $serverName -Name mssqlserver).Start()";
    exec( $restart_string );
    $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    
    // Wait until the service is fully started
    while (substr_count($servstring, "Running") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    }
}

function StopConn($serverName)
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $restart_string = "$powershell (get-service -ComputerName $serverName -Name mssqlserver).Stop()";
    exec( $restart_string );
    $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    
    // Wait until the service is fully stopped
    while (substr_count($servstring, "Stopped") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    }
}

function StartConn($serverName)
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    if (substr_count($servstring, "Running") != 1)
    {
        $restart_string = "$powershell (get-service -ComputerName $serverName -Name mssqlserver).Start()";
        exec( $restart_string );
    }
    $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    
    // Wait until the service is fully started
    while (substr_count($servstring, "Running") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $serverName -Name mssqlserver");
    }
}
?>