<?php
include_once 'autonomous_setup.php';
$conn1 = new PDO("sqlsrv:Server=$serverName", $username, $password);
$connId1 = ConnectionID($conn1);
$conn1 = null;

$conn2 = new PDO("sqlsrv:Server=$serverName", $username, $password);
$connId2 = ConnectionID($conn2);

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
