--TEST--
Test the TNIR keyword with enabled and disabled options and the MultiSubnetFailover keyword with true and false options
--SKIPIF--

--FILE--
<?php
// The way SYN packets are sent from the host is different depending on the combination of the TNIR and MultiSubnetFailover values
// TNIR Enabled, MSF Disabled:  One IP is attempted, followed by all IPs in parallel
// TNIR Enabled, MSF Enabled:   All IPs are attempted in parallel
// TNIR Disabled, MSF Disabled: All IPs are attempted one after another
// TNIR Disabled, MSF Enabled:  All IPs are attempted in parallel
// TNIR is enabled by default
// MultiSubnetFailover is disabled by default

require_once("MsSetup.inc");
$GLOBALS['uid'] = $uid;
$GLOBALS['pwd'] = $pwd;
$GLOBALS['server'] = $server;

function test_tnir( $TNIRValue, $MSFValue ) {
    $connectionInfo = ["UID" => $GLOBALS['uid'], "PWD" => $GLOBALS['pwd'], "TransparentNetworkIPResolution" => $TNIRValue, "MultiSubnetFailover" => $MSFValue];
    $MSFValueStr = ($MSFValue) ? 'true' : 'false';
    $start = microtime(true);
    $conn = sqlsrv_connect($GLOBALS['server'], $connectionInfo);
    if ($conn === false) {
        echo "Connection failed with TNIR $TNIRValue and MultiSubnetFailover $MSFValueStr.\n";
        print_r(sqlsrv_errors());
    } 
    else {
        echo "Connection successful with TNIR $TNIRValue and MultiSubnetFailover $MSFValueStr.\n";
        $connect_time = round(microtime(true) - $start, 2);
        echo "Time to connect is $connect_time sec.\n\n";
    }
    sqlsrv_close($conn);
}

test_tnir("Enabled", false);    // case temd (TNIR enabled; MultiSubnetFailover disabled)
test_tnir("Enabled", true);     // case teme
test_tnir("Disabled", false);   // case tdmd
test_tnir("Disabled", true);    // case tdme

// Interpreting output
// If the first resolved IP address can be connected, then the time to connect for all these cases is similar
// else if the second resolved IP address can be connected, then temd ~= tdmd > teme ~= tdme
// else tdmd > temd > teme ~= tdme
// note: the first test takes a bit longer since time is needed for the DNS to resolve the IP addresses of the server host name
?>

--EXPECTREGEX--
Connection successful with TNIR Enabled and MultiSubnetFailover false\.
Time to connect is [0-9]+.?[0-9]* sec\.

Connection successful with TNIR Enabled and MultiSubnetFailover true\.
Time to connect is [0-9]+.?[0-9]* sec\.

Connection successful with TNIR Disabled and MultiSubnetFailover false\.
Time to connect is [0-9]+.?[0-9]* sec\.

Connection successful with TNIR Disabled and MultiSubnetFailover true\.
Time to connect is [0-9]+.?[0-9]* sec\.