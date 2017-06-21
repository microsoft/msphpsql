<?php
function RestartConn($server)
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $restart_string = "$powershell (get-service -ComputerName $server -Name mssqlserver).Stop()";
    exec( $restart_string );
    $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    
    // Wait until the service is fully stopped
    while (substr_count($servstring, "Stopped") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    }
    $restart_string = "$powershell (get-service -ComputerName $server -Name mssqlserver).Start()";
    exec( $restart_string );
    $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    
    // Wait until the service is fully started
    while (substr_count($servstring, "Running") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    }
}

function StopConn($server)
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $restart_string = "$powershell (get-service -ComputerName $server -Name mssqlserver).Stop()";
    exec( $restart_string );
    $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    
    // Wait until the service is fully stopped
    while (substr_count($servstring, "Stopped") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    }
}

function StartConn($server)
{
    $powershell = "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe";
    $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    if (substr_count($servstring, "Running") != 1)
    {
        $restart_string = "$powershell (get-service -ComputerName $server -Name mssqlserver).Start()";
        exec( $restart_string );
    }
    $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    
    // Wait until the service is fully started
    while (substr_count($servstring, "Running") != 1)
    {
        sleep(1);
        $servstring = shell_exec("$powershell get-service -ComputerName $server -Name mssqlserver");
    }
}
?>