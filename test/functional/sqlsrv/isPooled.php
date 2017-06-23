<?php
include 'MsCommon.inc';
$conn1 = Connect();
$connId1 = ConnectionID($conn1);
sqlsrv_close($conn1);

$conn2 = Connect();
$connId2 = ConnectionID($conn2);
sqlsrv_close($conn2);

if ($connId1 === $connId2){
    echo "Pooled\n";
}else{
    echo "Not Pooled\n";
}

function ConnectionID($conn)
{
    $tsql = "SELECT [connection_id] FROM [sys].[dm_exec_connections] where session_id = @@SPID";
    $stmt = sqlsrv_query($conn, $tsql);
    sqlsrv_fetch($stmt);
    $connID = sqlsrv_get_field($stmt, 0);
    sqlsrv_free_stmt($stmt);
    return ($connID);
}
?>
