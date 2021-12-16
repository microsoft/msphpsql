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
function connectVerifyOutput($server, $connectionOptions, $expected = null)
{
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if (is_null($expected) || (strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false)) {
            print_r(sqlsrv_errors());
        }
    }
}

function testValidValues($msodbcsqlMaj, $server, $connectionOptions)
{
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
    $connectionOptions['Driver']=$value;
    connectVerifyOutput($server, $connectionOptions);

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
    $value = "ODBC Driver 13 for SQL Server";
    $connectionOptions['Driver']=$value;
    $connectionOptions['ColumnEncryption']='Enabled';

    $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server";

    connectVerifyOutput($server, $connectionOptions, $expected);
}

function testWrongODBC($msodbcsqlMaj, $server, $connectionOptions)
{
    $value = "ODBC Driver 11 for SQL Server";
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
