--TEST--
Test ColumnEncryption values.
--DESCRIPTION--
This test checks that connection fails when ColumnEncryption is set to nonsense,
or when it is set to an incorrect protocol. Then it checks that connection succeeds when
the attestation URL is incorrect.
--SKIPIF--
<?php require("skipif_not_hgs.inc"); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("AE_v2_values.inc");
require_once("pdo_AE_functions.inc");

// Test with random nonsense. Connection should fail.
$options = "sqlsrv:Server=$server;database=$databaseName;ColumnEncryption=xyz";

try {
    $conn = new PDO($options, $uid, $pwd);
    die("Connection should have failed!\n");
} catch(PDOException $error) {
    $e = $error->errorInfo;
    checkErrors($e, array('CE400', '0'));
}

// Test with incorrect protocol and good attestation URL. Connection should fail.
// Insert a rogue 'x' into the protocol part of the attestation.
$comma = strpos($attestation, ',');
$badProtocol = substr_replace($attestation, 'x', $comma, 0);
$options = "sqlsrv:Server=$server;database=$databaseName;ColumnEncryption=$badProtocol";

try {
    $conn = new PDO($options, $uid, $pwd);
    die("Connection should have failed!\n");
} catch(Exception $error) {
    $e = $error->errorInfo;
    checkErrors($e, array('CE400', '0'));
}

// Test with good protocol and incorrect attestation URL. Connection should succeed
// because the URL is only checked when an enclave computation is attempted.
$badURL = substr_replace($attestation, 'x', $comma+1, 0);
$options = "sqlsrv:Server=$server;database=$databaseName;ColumnEncryption=$badURL";

try {
    $conn = new PDO($options, $uid, $pwd);
} catch(Exception $error) {
    print_r($error);
    die("Connecting with a bad attestation URL should have succeeded!\n");
}

echo "Done.\n";

?>
--EXPECT--
Done.
