<?php
if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");

$connectionInfo = array("UID"=>$userName, "PWD"=>$userPassword);

$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    die("skip Could not connect during SKIPIF.");
}

$tsql = <<<SQL
SELECT c.session_id, c.net_transport
FROM sys.dm_exec_connections AS c  
JOIN sys.dm_exec_sessions AS s  
    ON c.session_id = s.session_id  
WHERE c.session_id = @@SPID;
SQL;

// Check the transport protocol for the current connection
$stmt = sqlsrv_query($conn, $tsql);
if (sqlsrv_fetch($stmt)) {
    $prot = sqlsrv_get_field($stmt, 1);
    if ($prot != 'TCP'){
        die("skip Not using a TCP protocol.");
    }
} else {
    die("skip Failed to fetch SPID and transport protocol.");
}

?>
