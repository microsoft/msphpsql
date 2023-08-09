--TEST--
variety of connection parameters.
--DESCRIPTION--
The equivalent of sqlsrv_testConnection.phpt but for running outside Windows to verify that GitHub issue 430 is fixed 
--SKIPIF--
<?php
require('skipif_azure.inc');

if ((strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN')) {
    die("Skip Test on Linux or Mac only.");
}
?>
--FILE--
<?php

    require 'MsSetup.inc';
    
    function connect($options=array())
    {
        require 'MsSetup.inc';
        if (!isset($options['UID']) && !isset($options['uid'])) {
            $options['uid'] = $uid;
        }
        if (!isset($options['pwd']) && !isset($options['PWD'])) {
            $options['pwd'] = $pwd;
        }
        if (!isset($options['Database'])) {
            $options['database'] = $databaseName;
        }
        return sqlsrv_connect($server, $options);
    }

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
        
    echo "Test sqlsrv_connect with driver injection\n";

    $conn = sqlsrv_connect( $server, array( "UID" => $userName, "PWD" => "$userPassword;Driver={SQL Server Native Client 11.0}}" ));
    
    if (!$conn) {
        var_dump(sqlsrv_errors());
    } else {
        sqlsrv_close($conn);
        die("Shouldn't have opened the connection.");
    }

    echo "Test sqlsrv_connect with driver injection (2)\n";
    $conn = sqlsrv_connect( $server, array( "UID" => $userName, "PWD" => "{$userPassword};Driver={SQL Server Native Client 11.0}" ));
    
    if (!$conn) {
        var_dump(sqlsrv_errors());
    } else {
        sqlsrv_close($conn);
        die("Shouldn't have opened the connection.");
    }

    echo "Test sqlsrv_connect with driver injection (3)\n";
    $conn = sqlsrv_connect( $server, array( "UID" => "sa", "PWD" => "{$userPassword}};Driver={SQL Server Native Client 11.0}" ));
    
    if (!$conn) {
        var_dump(sqlsrv_errors());
    } else {
        sqlsrv_close($conn);
        die("Shouldn't have opened the connection.");
    }

    // Test a bunch of options. The Failover_Partner keyword does not work
    // on Unix, so we replace it with MultiSubnetFailover instead.
    $conn_options_all = array( "APP" => "PHP Unit Test",
                               "ConnectionPooling" => true,
                               "Database" => $databaseName,
                               "Encrypt" => 0,
                               "LoginTimeout" => 120,
                               "MultipleActiveResultSets" => false,
                               "QuotedId" => false,
                               "TransactionIsolation" => SQLSRV_TXN_READ_COMMITTED,
                               "TrustServerCertificate" => 1,
                               "WSID" => "JAYKINT1" );
    $conn_options_int = array( "APP" => "PHP Unit Test",
                               "ConnectionPooling" => false,
                               "Database" => $databaseName,
                               "Encrypt" => 0,
                               "LoginTimeout" => 120,
                               "MultipleActiveResultSets" => false,
                               "QuotedId" => true,
                               "TransactionIsolation" => SQLSRV_TXN_READ_COMMITTED,
                               "TrustServerCertificate" => 1,
                               "WSID" => "JAYKINT1" );
                               
    echo "Test sqlsrv_connect with all options\n";
    $conn_options_all['MultiSubnetFailover'] = true;
    $conn = connect($conn_options_all);
    print_r(sqlsrv_errors()[0]);
    print_r(sqlsrv_errors()[1]);
    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    
    echo "Test sqlsrv_connect with all options and integrated auth\n";
    $conn_options_int['MultiSubnetFailover'] = true;
    $conn = connect($conn_options_int);
    print_r(sqlsrv_errors()[0]);
    print_r(sqlsrv_errors()[1]);
    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    }

     sqlsrv_close($conn);
     echo "Test succeeded.\n";
?>
--EXPECTREGEX--
Test sqlsrv_connect with driver injection
array\(1\) \{
  \[0\]=>
  array\(6\) \{
    \[0\]=>
    string\(5\) "28000"
    \["SQLSTATE"\]=>
    string\(5\) "28000"
    \[1\]=>
    int\(18456\)
    \["code"\]=>
    int\(18456\)
    \[2\]=>
    string\(81\) ".*Login failed for user 'sa'."
    \["message"\]=>
    string\(81\) ".*Login failed for user 'sa'."
  \}
\}
Test sqlsrv_connect with driver injection \(2\)
array\(1\) \{
  \[0\]=>
  array\(6\) \{
    \[0\]=>
    string\(5\) "IMSSP"
    \["SQLSTATE"\]=>
    string\(5\) "IMSSP"
    \[1\]=>
    int\(-4\)
    \["code"\]=>
    int\(-4\)
    \[2\]=>
    string\(140\) "An unescaped right brace \(\}\) was found in either the user name or password.  All right braces must be escaped with another right brace \(\}\}\)."
    \["message"\]=>
    string\(140\) "An unescaped right brace \(\}\) was found in either the user name or password.  All right braces must be escaped with another right brace \(\}\}\)."
  \}
\}
Test sqlsrv_connect with driver injection \(3\)
array\(1\) \{
  \[0\]=>
  array\(6\) \{
    \[0\]=>
    string\(5\) "IMSSP"
    \["SQLSTATE"\]=>
    string\(5\) "IMSSP"
    \[1\]=>
    int\(-4\)
    \["code"\]=>
    int\(-4\)
    \[2\]=>
    string\(140\) "An unescaped right brace \(\}\) was found in either the user name or password.  All right braces must be escaped with another right brace \(\}\}\)."
    \["message"\]=>
    string\(140\) "An unescaped right brace \(\}\) was found in either the user name or password.  All right braces must be escaped with another right brace \(\}\}\)."
  \}
\}
Test sqlsrv_connect with all options
Array
\(
    \[0\] => 01000
    \[SQLSTATE\] => 01000
    \[1\] => 5701
    \[code\] => 5701
    \[2\] => .*Changed database context to '.*'.
    \[message\] => .*Changed database context to '.*'.
\)
Array
\(
    \[0\] => 01000
    \[SQLSTATE\] => 01000
    \[1\] => 5703
    \[code\] => 5703
    \[2\] => .*Changed language setting to us_english.
    \[message\] => .*Changed language setting to us_english.
\)
Test sqlsrv_connect with all options and integrated auth
Array
\(
    \[0\] => 01000
    \[SQLSTATE\] => 01000
    \[1\] => 5701
    \[code\] => 5701
    \[2\] => .*Changed database context to '.*'.
    \[message\] => .*Changed database context to '.*'.
\)
Array
\(
    \[0\] => 01000
    \[SQLSTATE\] => 01000
    \[1\] => 5703
    \[code\] => 5703
    \[2\] => .*Changed language setting to us_english.
    \[message\] => .*Changed language setting to us_english.
\)
Test succeeded.
