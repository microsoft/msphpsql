--TEST--
The TCP prerequisite checks physical transport with MARS enabled and disabled
--SKIPIF--
<?php
require('skipif.inc');
require_once('MsCommon.inc');
try {
    $connection = connect(array(), false);
    $statement = $connection->query("SELECT CONVERT(nvarchar(40), CONNECTIONPROPERTY('physical_net_transport'))");
    $transport = $statement->fetchColumn();
    if ($transport === false || $transport === null || $transport === '') {
        die("skip Could not determine physical transport protocol.");
    }
    if ($transport !== 'TCP') {
        die("skip Not using a TCP protocol.");
    }
} catch (PDOException $e) {
    die("skip Could not establish the TCP test prerequisite.");
}
?>
--FILE--
<?php
require_once('MsSetup.inc');
require_once('MsCommon.inc');

// Exercise the actual guard against each connection, not just a copy of its query.
function connect($options = array(), $catchErrors = true)
{
    global $transportConnection;
    return $transportConnection;
}

foreach (array(false, true) as $mars) {
    $dsn = "sqlsrv:Server=$server;Database=$databaseName;Driver=$driver;Encrypt=$encrypt;"
        . "ConnectionPooling=false;MultipleActiveResultSets=" . ($mars ? "true" : "false");
    $transportConnection = new PDO($dsn, $uid, $pwd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $pending = null;
    try {
        if ($mars) {
            $pending = $transportConnection->query("SELECT 1 AS n UNION ALL SELECT 2");
            var_dump($pending->fetchColumn() == 1);
        }

        require('skipif_protocol_not_tcp.inc');
        echo $mars ? "MARS TCP guard passed\n" : "Non-MARS TCP guard passed\n";

        if ($pending !== null) {
            var_dump($pending->fetchColumn() == 2);
        }
    } finally {
        if ($pending !== null) {
            $pending->closeCursor();
        }
        unset($stmt, $conn, $pending, $transportConnection);
    }
}
?>
--EXPECT--
Non-MARS TCP guard passed
bool(true)
MARS TCP guard passed
bool(true)
