<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");
require_once("MsCommon.inc");

try {
    $conn = connect(null, false);
} catch (PDOException $e) {
    die("skip Could not connect during SKIPIF.");
}

$tsql = "SELECT CONVERT(nvarchar(40), CONNECTIONPROPERTY('physical_net_transport'))";

try {
    // MARS logical connections can report "Session" instead of the underlying transport.
    $stmt = $conn->query($tsql);
    if ($stmt === false) {
        die("skip Failed to query physical transport protocol.");
    }
    $prot = $stmt->fetchColumn();
    $stmt->closeCursor();

    if ($prot === false || $prot === null || $prot === '') {
        die("skip Could not determine physical transport protocol.");
    }
    if ($prot !== 'TCP') {
        die("skip Not using a TCP protocol.");
    }
} catch (PDOException $e) {
    die("skip Failed to query physical transport protocol.");
}
?>
