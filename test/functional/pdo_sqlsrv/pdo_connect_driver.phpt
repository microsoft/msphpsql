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
echo "Done";
// end test

///////////////////////////
function connectVerifyOutput($connectionOptions, $expected = '')
{
    global $server, $uid, $pwd;
    
    try {
        $conn = new PDO("sqlsrv:server = $server ; $connectionOptions", $uid, $pwd);
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), $expected) === false) {
            print_r($e->getMessage());
            echo "\n";
        }
    }
}

function testValidValues()
{
    global $msodbcsqlMaj;
    
    $value = "";
    // The major version number of ODBC 11 can be 11 or 12
    // Test with {}
    switch ($msodbcsqlMaj) {
        case 17:
            $value = "{ODBC Driver 17 for SQL Server}";
            break;
        case 14:
        case 13:
            $value = "{ODBC Driver 13 for SQL Server}";
            break;
        case 12:
        case 11:
            $value = "{ODBC Driver 11 for SQL Server}";
            break;            
        default:
            $value = "invalid value $msodbcsqlMaj";
    }
    $connectionOptions = "Driver = $value";
    connectVerifyOutput($connectionOptions);
    
    // Test without {}
    switch ($msodbcsqlMaj) {
        case 17:
            $value = "ODBC Driver 17 for SQL Server";
            break;
        case 14:
        case 13:
            $value = "ODBC Driver 13 for SQL Server";
            break;
        case 12:
        case 11:
            $value = "ODBC Driver 11 for SQL Server";
            break;            
        default:
            $value = "invalid value $msodbcsqlMaj";
    }
    
    $connectionOptions = "Driver = $value";
    connectVerifyOutput($connectionOptions);
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
        connectVerifyOutput($connectionOptions, $expected);
    }
}

function testEncryptedWithODBC() 
{
    global $msodbcsqlMaj, $server, $uid, $pwd;
       
    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions = "Driver = $value; ColumnEncryption = Enabled;"; 
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
    } else {
        $expected = "An invalid keyword 'ColumnEncryption' was specified in the DSN string.";
    }

    connectVerifyOutput($connectionOptions, $expected);
}

function testWrongODBC()
{
    global $msodbcsqlMaj;
    
    // TODO: this will change once ODBC 17 is officially released
    $value = "ODBC Driver 17 for SQL Server";
    if ($msodbcsqlMaj == 17 || $msodbcsqlMaj < 13) {
        $value = "ODBC Driver 13 for SQL Server";
    }
    $connectionOptions = "Driver = $value;";
    $expected = "The specified ODBC Driver is not found.";
    
    connectVerifyOutput($connectionOptions, $expected);
}

?>
--EXPECT--
Done