<?php
include 'MsSetup.inc';

$conn1 = new PDO("sqlsrv:Server=$server; database=$databaseName; driver=$driver", $uid, $pwd);
$connId1 = connectionID($conn1);
unset($conn1);

$conn2 = new PDO("sqlsrv:Server=$server; database=$databaseName; driver=$driver", $uid, $pwd);
$connId2 = connectionID($conn2);

if ($connId1 === $connId2){
    echo "Pooled\n";
}else{
    echo "Not Pooled\n";
}

// The following is not applicable in Azure
$azure = isAzure($conn2);
if (!$azure) {
    try {
        $conn2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn2->prepare("SET NOCOUNT ON; USE tempdb; SELECT 1/0 AS col1");
        $stmt->execute();
    } catch (PDOException $e) {
        checkErrorInfo($stmt, $e);
    }
}

unset($conn2);

function connectionID($conn)
{
    $tsql = "SELECT [connection_id] FROM [sys].[dm_exec_connections] where session_id = @@SPID";
    $stmt = $conn->query($tsql);
    $connID = $stmt->fetchColumn(0);
    $stmt->closeCursor();
    $stmt = null;
    return ($connID);
}

function isAzure($conn)
{
    try {
        $tsql = "SELECT SERVERPROPERTY ('edition')";
        $stmt = $conn->query($tsql);

        $result = $stmt->fetch(PDO::FETCH_NUM);
        $edition = $result[0];
        
        if ($edition === "SQL Azure") {
            return true;
        } else {
            return false;
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
        die("Could not fetch server property.");
    }
}

function checkErrorInfo($stmt, $err)
{
    $expected = "*Divide by zero error encountered*";
    $idx = count($err->errorInfo) - 1;
    $failed = false;
    if ($idx != 5 || !fnmatch($expected, $err->errorInfo[$idx])) {
        echo "Error message unexpected!\n";
        $failed = true;
    }
    if ($err->errorInfo !== $stmt->errorInfo()) {
        echo "Error info arrays should match!\n";
        $failed = true;
    } 
    if ($failed) {
        var_dump($err);
    }
}
?>
