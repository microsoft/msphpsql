--TEST--
Test new connection keyword ColumnEncryption with different input values
--DESCRIPTION--
Some test cases return errors as expected. For testing purposes, an enclave enabled 
SQL Server and the HGS server are the same instance. If the server is HGS enabled,
the error message of one test case is not the same.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsErrors', 0);
require('MsSetup.inc');

$connectionOptions = array("Database"=>$database,"UID"=>$userName, "PWD"=>$userPassword);
testColumnEncryption($server, $connectionOptions);
echo "Done";

function testColumnEncryption($server, $connectionOptions)
{
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        print_r(sqlsrv_errors());
    }
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    $msodbcsqlMaj = explode(".", $msodbcsql_ver)[0];

    // Next, check if the server is HGS enabled
    $hgsEnabled = true;
    $serverInfo = sqlsrv_server_info($conn);
    if (strpos($serverInfo['SQLServerName'], 'PHPHGS') === false) {
        $hgsEnabled = false;
    }
    
    // Only works for ODBC 17
    $connectionOptions['ColumnEncryption'] = 'Enabled';
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if ($msodbcsqlMaj < 17) {
            $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
            if (strcasecmp(sqlsrv_errors($conn)[0]['message'], $expected) != 0) {
                print_r(sqlsrv_errors());
            }
        } else {
            echo "Test case 1 failed:\n";
            print_r(sqlsrv_errors());
        }
    }

    // Works for ODBC 17, ODBC 13
    $connectionOptions['ColumnEncryption']='Disabled';
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if ($msodbcsqlMaj < 13) {
            $expected = "Invalid connection string attribute";
            if (strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false) {
                print_r(sqlsrv_errors());
            }
        } else {
            echo "Test case 2 failed:\n";
            print_r(sqlsrv_errors());
        }
    } else {
        sqlsrv_close($conn);
    }

    // Should fail for all ODBC drivers - but the error message returned depends on the server
    $expected = "Invalid value specified for connection string attribute 'ColumnEncryption'";
    if ($hgsEnabled) {
        $expected = "Requested attestation protocol is invalid.";
    }
    
    $connectionOptions['ColumnEncryption']='false';
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if (strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false) {
            echo "Test case 3 failed:\n";
            print_r(sqlsrv_errors());
        }
    }
    
    $expected = "Invalid value type for option ColumnEncryption was specified.  String type was expected.";

    // should fail for all ODBC drivers with the above error message
    $connectionOptions['ColumnEncryption']=true;
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if (strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false) {
            echo "Test case 4 failed:\n";
            print_r(sqlsrv_errors());
        }
    }
    
    // should fail for all ODBC drivers with the above error message
    $connectionOptions['ColumnEncryption']=false;
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        if (strpos(sqlsrv_errors($conn)[0]['message'], $expected) === false) {
            echo "Test case 5 failed:\n";
            print_r(sqlsrv_errors());
        }
    }
}
?>
--EXPECT--
Done