--TEST--
Test Chinese locale in Linux
--DESCRIPTION--
This test will invoke another php scirpt that is saved as GB2312(Simplified Chinese) ANSI format,
namely pdo_test_gb18030.php.
To run this test, create a temporary database first with the correct collation
    CREATE DATABASE [GB18030test]
    COLLATE Chinese_PRC_CI_AS
Next, set the correct locale and convert the php script, like this:
    export LC_ALL=zh_CN.gb18030
    iconv -c -f GB2312 -t GB18030 pdo_test_gb18030.php > test_gb18030.php
Drop the temporary database when the test is finished.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php 
require('skipif_unix_ansitests.inc'); 
$loc = setlocale(LC_ALL, 'zh_CN.gb18030');
if (empty($loc)) {
    die("skip required gb18030 locale not available");
}
--FILE--
<?php
function runTest($conn, $tempDB)
{
    $query = "CREATE DATABASE $tempDB COLLATE Chinese_PRC_CI_AS";
    $conn->exec($query);
    
    shell_exec("export LC_ALL=zh_CN.gb18030");
    shell_exec("iconv -c -f GB2312 -t GB18030 pdo_test_gb18030.php > test_gb18030.php");
    
    print_r(shell_exec(PHP_BINARY." ".dirname(__FILE__)."/test_gb18030.php $tempDB"));   
}

try {
    $tempDB = 'GB18030test' . rand(1, 100);
    require_once('MsSetup.inc');
    
    $conn = new PDO("sqlsrv:server = $server;database=master;driver=$driver", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    runTest($conn, $tempDB);
} catch (PDOException $e) {
    echo $e->getMessage() . PHP_EOL;
} finally {
    $query = "DROP DATABASE $tempDB";
    $conn->exec($query);
    unset($conn);
}

?>
--EXPECT--
Done
