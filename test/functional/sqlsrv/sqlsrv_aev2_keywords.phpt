--TEST--
Test ColumnEncryption values.
--DESCRIPTION--
This test checks that connection fails when ColumnEncryption is set to nonsense,
or when it is set to a bad protocol. Then it checks that connection succeeds when
the attestation URL is bad.
--FILE--
<?php
include("MsSetup.inc");
include("AE_v2_values.inc");
include("sqlsrv_AE_functions.inc");

// Test with random nonsense. Connection should fail.
$options = array('database'=>$database,
                 'uid'=>$userName,
                 'pwd'=>$userPassword,
                 'driver'=$driver,
                 'ColumnEncryption'=>"xyz",
                 );

$conn = sqlsrv_connect($server, $options);
if (!$conn) {
    $e = sqlsrv_errors();
    checkErrors($e, array('CE400', '0'));
} else {
    print_r("Connecting with nonsense should have failed!\n");
}

// Test with bad protocol and good attestation URL. Connection should fail.
// Insert a rogue 'x' into the protocol part of the attestation.
$comma = strpos($attestation, ',');
$badProtocol = substr_replace($attestation, 'x', $comma, 0);
$options = array('database'=>$database,
                 'uid'=>$userName,
                 'pwd'=>$userPassword,
                 'driver'=$driver,
                 'ColumnEncryption'=>$badProtocol,
                 );

$conn = sqlsrv_connect($server, $options);
if (!$conn) {
    $e = sqlsrv_errors();
    checkErrors($e, array('CE400', '0'));
} else {
    print_r("Connecting with a bad attestation protocol should have failed!\n");
}

// Test with good protocol and bad attestation URL. Connection should succeed
// because the URL is only checked when an enclave computation is attempted.
$badURL = substr_replace($attestation, 'x', $comma+1, 0);
$options = array('database'=>$database,
                 'uid'=>$userName,
                 'pwd'=>$userPassword,
                 'ColumnEncryption'=>$badURL,
                 );

$conn = sqlsrv_connect($server, $options);
if (!$conn) {
    print_r(sqlsrv_errors());
    die("Connecting with a bad attestation URL should have succeeded!\n");
}

echo "Done.\n";

?>
--EXPECT--
Done.
