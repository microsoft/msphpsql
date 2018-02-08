--TEST--
Test new connection keyword Driver with valid and invalid values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsErrors', 0);
require_once('MsSetup.inc');

$connectionOptions = array("Database"=>$database, "UID"=>$userName, "PWD"=>$userPassword);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn === false) {
    print_r(sqlsrv_errors());
}
$msodbcsqlVer = sqlsrv_client_info($conn)['DriverVer'];
$msodbcsqlMaj = explode(".", $msodbcsqlVer)[0];
sqlsrv_close($conn);

// start test
testValidValues($msodbcsqlMaj, $server, $connectionOptions);
testInvalidValues($msodbcsqlMaj, $server, $connectionOptions);
testEncryptedWithODBC($msodbcsqlMaj, $server, $connectionOptions);
testWrongODBC($msodbcsqlMaj, $server, $connectionOptions);
echo "Done";
// end test

///////////////////////////
function connectVerifyOutput($server, $connectionOptions, $expected = '')
{
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if (strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false) {
            print_r(sqlsrv_errors());
        }
    }
}

function testValidValues($msodbcsqlMaj, $server, $connectionOptions)
{
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
    $connectionOptions['Driver']=$value;
    connectVerifyOutput($server, $connectionOptions);

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

    $connectionOptions['Driver']=$value;
    connectVerifyOutput($server, $connectionOptions);
}

function testInvalidValues($msodbcsqlMaj, $server, $connectionOptions)
{
    $values = array("{SQL Server Native Client 11.0}",
                    "SQL Server Native Client 11.0",
                    "ODBC Driver 00 for SQL Server");

    foreach ($values as $value) {
        $connectionOptions['Driver']=$value;
        $expected = "Invalid value $value was specified for Driver option.";
        connectVerifyOutput($server, $connectionOptions, $expected);
    }

    $values = array(123, false);

    foreach ($values as $value) {
        $connectionOptions['Driver']=$value;
        $expected = "Invalid value type for option Driver was specified.  String type was expected.";
        connectVerifyOutput($server, $connectionOptions, $expected);
    }
}

function testEncryptedWithODBC($msodbcsqlMaj, $server, $connectionOptions)
{
    // Skip this function if running outside Windows
    if (!strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
        return;
    }
    
    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions['Driver']=$value;
    $connectionOptions['ColumnEncryption']='Enabled';

    $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";

    connectVerifyOutput($server, $connectionOptions, $expected);

    // TODO: the following block will change once ODBC 17 is officially released
    $value = "ODBC Driver 17 for SQL Server";
    $connectionOptions['Driver']=$value;
    $connectionOptions['ColumnEncryption']='Enabled';

    $success = "Successfully connected with column encryption.";
    $expected = "The specified ODBC Driver is not found.";
    $message = $success;

    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        $message = sqlsrv_errors($conn)[0]['message'];
    }

    if ($msodbcsqlMaj == 17) {
        // this indicates that OCBC 17 is the only available driver
        if (strcmp($message, $success)) {
            print_r($message);
        }
    } else {
        // OCBC 17 might or might not exist
        if (strcmp($message, $success)) {
            if (strpos($message, $expected) === false) {
                print_r($message);
            }
        }
    }
}

function testWrongODBC($msodbcsqlMaj, $server, $connectionOptions)
{
    // TODO: this will change once ODBC 17 is officially released
    $value = "ODBC Driver 17 for SQL Server";
    if ($msodbcsqlMaj == 17 || $msodbcsqlMaj < 13) {
        $value = "ODBC Driver 13 for SQL Server";
    }

    $connectionOptions['Driver']=$value;
    $expected = "The specified ODBC Driver is not found.";

    connectVerifyOutput($server, $connectionOptions, $expected);
}

?>
--EXPECT--
Done
