<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");

try {
    $conn = new PDO("sqlsrv:server = $server", $uid, $pwd);
} catch (PDOException $e) {
    die("skip Could not connect during SKIPIF.");
}

$tsql = <<<SQL
SELECT c.session_id, c.net_transport
FROM sys.dm_exec_connections AS c  
JOIN sys.dm_exec_sessions AS s  
    ON c.session_id = s.session_id  
WHERE c.session_id = @@SPID;
SQL;

try {
    // Check the transport protocol for the current connection
    $stmt = $conn->query($tsql);
    $prot = $stmt->fetchColumn(1);

    if ($prot != 'TCP') {
        die("skip Not using a TCP protocol.");
    }
} catch (PDOException $e) {
    die("skip Failed to fetch SPID and transport protocol.");
}
?>
