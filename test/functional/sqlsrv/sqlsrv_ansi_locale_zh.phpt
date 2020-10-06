--TEST--
Test Chinese locale in Linux
--DESCRIPTION--
This test requires ODBC Driver 17.6 or above and will invoke another php script that 
is saved as GB2312(Simplified Chinese) ANSI format, namely sqlsrv_test_gb18030.php.
To run this test, create a temporary database first with the correct collation
    CREATE DATABASE [GB18030test]
    COLLATE Chinese_PRC_CI_AS
Next, set the correct locale and convert the php script, like this:
    export LC_ALL=zh_CN.gb18030
    iconv -c -f GB2312 -t GB18030 sqlsrv_test_gb18030.php > test_gb18030.php
Drop the temporary database when the test is finished.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php 
require_once('skipif_unix_ansitests.inc'); 
$loc = setlocale(LC_ALL, 'zh_CN.gb18030');
if (empty($loc)) {
    die("skip required gb18030 locale not available");
}

require_once('MsSetup.inc');
$connectionInfo = array("UID"=>$userName, "PWD"=>$userPassword);
$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    die("skip Could not connect during SKIPIF.");
}

$msodbcsqlVer = sqlsrv_client_info($conn)["DriverVer"];
$msodbcsqlMaj = explode(".", $msodbcsqlVer)[0];
$msodbcsqlMin = explode(".", $msodbcsqlVer)[1];

if ($msodbcsqlMaj < 17) {
    die("skip Unsupported ODBC driver version");
}

if ($msodbcsqlMaj == 17 && $msodbcsqlMin < 6) {
    die("skip Unsupported ODBC driver version");
}
?>

--FILE--
<?php

require_once('MsSetup.inc');

try {
    $options = array("Database"=>"master", "UID"=>$userName, "PWD"=>$userPassword);
    $conn = sqlsrv_connect($server, $options);
    if( $conn === false ) {
        throw new Error("Failed to connect");
    }

    $tempDB = 'GB18030test' . rand(1, 100);
    $query = "CREATE DATABASE $tempDB COLLATE Chinese_PRC_CI_AS";
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false) {
        throw new Error("Failed to create the database $tempDB");
    }

    shell_exec("export LC_ALL=zh_CN.gb18030");
    shell_exec("iconv -c -f GB2312 -t GB18030 ".dirname(__FILE__)."/sqlsrv_test_gb18030.php > ".dirname(__FILE__)."/test_gb18030.php");

    print_r(shell_exec(PHP_BINARY." ".dirname(__FILE__)."/test_gb18030.php $tempDB"));
} catch (Error $err) {
    echo $err->getMessage() . PHP_EOL;
    print_r(sqlsrv_errors());
} finally {
    if ($conn) {
        if (!empty($tempDB)) {
            $query = "DROP DATABASE $tempDB";
            sqlsrv_query($conn, $query);
        }
        sqlsrv_close($conn);
    }
}

?>
--EXPECT--
Done
