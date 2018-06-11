<?php
include 'MsSetup.inc';
$conn1 = new PDO("sqlsrv:Server=$server; database=$databaseName; driver=$driver", $uid, $pwd);
$connId1 = ConnectionID($conn1);
$conn1 = null;

$conn2 = new PDO("sqlsrv:Server=$server; database=$databaseName; driver=$driver", $uid, $pwd);
$connId2 = ConnectionID($conn2);
$conn2 = null;

if ($connId1 === $connId2){
    echo "Pooled\n";
}else{
    echo "Not Pooled\n";
}

function ConnectionID($conn)
{
    $tsql = "SELECT [connection_id] FROM [sys].[dm_exec_connections] where session_id = @@SPID";
    $stmt = $conn->query($tsql);
    $connID = $stmt->fetchColumn(0);
    $stmt->closeCursor();
    $stmt = null;
    return ($connID);
}
?>
