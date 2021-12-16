--TEST--
Test new connection keyword Driver with valid and invalid values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsSetup.inc');

try {
    $conn = new PDO("sqlsrv:server = $server", $uid, $pwd);
    $msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)['DriverVer'];
    $msodbcsqlMaj = explode(".", $msodbcsqlVer)[0];
} catch(PDOException $e) {
    echo "Failed to connect\n";
    print_r($e->getMessage());
    echo "\n";
}

$conn = null;

// start test
testValidValues();
testInvalidValues();
testEncryptedWithODBC();
testWrongODBC();
echo "Done" . PHP_EOL;
// end test

///////////////////////////
function connectVerifyOutput($connectionOptions, $testcase, $expected = null)
{
    global $server, $uid, $pwd;

    try {
        $conn = new PDO("sqlsrv:server = $server ; $connectionOptions", $uid, $pwd);
        if (!is_null($expected)) {
            echo "'$testcase' is expected to fail!" . PHP_EOL;
        }
    } catch(PDOException $e) {
        if (is_null($expected)) {
            echo "'$testcase' is expected to pass!" . PHP_EOL;
        } elseif (strpos($e->getMessage(), $expected) === false) {
            echo "The error returned for '$testcase' is unexpected:" . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

function testValidValues()
{
    global $msodbcsqlMaj;

    $value = "";
    // The major version number of ODBC 13 can be 13 or 14
    // Test with {}
    switch ($msodbcsqlMaj) {
        case 17:
            $value = "{ODBC Driver 17 for SQL Server}";
            break;
        case 18:
            $value = "{ODBC Driver 18 for SQL Server}";
            break;
        case 14:
        case 13:
            $value = "{ODBC Driver 13 for SQL Server}";
            break;
        default:
            $value = "invalid value $msodbcsqlMaj";
    }
    $connectionOptions = "Driver = $value";
    connectVerifyOutput($connectionOptions, "Driver with curly brackets");

    // Test without {}
    switch ($msodbcsqlMaj) {
        case 17:
            $value = "ODBC Driver 17 for SQL Server";
            break;
        case 18:
            $value = "ODBC Driver 18 for SQL Server";
            break;
        case 14:
        case 13:
            $value = "ODBC Driver 13 for SQL Server";
            break;
        default:
            $value = "invalid value $msodbcsqlMaj";
    }

    $connectionOptions = "Driver = $value";
    connectVerifyOutput($connectionOptions, "Driver without curly brackets");
}

function testInvalidValues()
{
    $values = array("{SQL Server Native Client 11.0}",
                    "SQL Server Native Client 11.0",
                    "ODBC Driver 00 for SQL Server",
                    123,
                    false);

    foreach ($values as $value) {
        $connectionOptions = "Driver = $value";
        $expected = "Invalid value $value was specified for Driver option.";
        connectVerifyOutput($connectionOptions, "Invalid driver $value", $expected);
    }
}

function testEncryptedWithODBC()
{
    global $msodbcsqlMaj, $server, $uid, $pwd;

    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions = "Driver = $value; ColumnEncryption = Enabled;";

    $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server";

    connectVerifyOutput($connectionOptions, "Using ODBC 13 for AE", $expected);
}

function testWrongODBC()
{
    global $msodbcsqlMaj;

    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions = "Driver = $value;";
    $expected = "The specified ODBC Driver is not found.";

    connectVerifyOutput($connectionOptions, "Connect with ODBC 13", $expected);
}

?>
--EXPECT--
Done
