<?php
/*
This ansi test is invoked by pdo_ansi_locale_zh.phpt
*/
function insertText($conn, $text, $hexValue)
{
    $hex = bin2hex($text);
    if ($hex !== $hexValue) {
        "Expected $hexValue but got $hex" . PHP_EOL;
    }
    $tsql = "INSERT INTO test1 VALUES (?)";
    $stmt = $conn->prepare($tsql);

    $stmt->execute(array($text));
}

require_once('MsSetup.inc');

$tempDB = ($_SERVER['argv'][1]);

setlocale(LC_ALL, 'zh_CN.gb18030');

try {
    $conn = new PDO("sqlsrv:server = $server;database=$tempDB;driver=$driver", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);

    $tsql = "CREATE TABLE test1([id] int identity, [name] [varchar](50) NULL)";
    $stmt = $conn->query($tsql);

    // Next, insert the strings
    $inputs = array('中文', '你好', '未找到信息', '获取更多');
    $hexValues = array('d6d0cec4', 'c4e3bac3', 'ceb4d5d2b5bdd0c5cfa2', 'bbf1c8a1b8fcb6e0');
    for ($i = 0; $i < 4; $i++) {
        insertText($conn, $inputs[$i], $hexValues[$i]);
    }

    // Next, fetch the strings
    $tsql = "SELECT * FROM test1";
    $stmt = $conn->query($tsql);

    $i = 0;
    while ($result = $stmt->fetch(PDO::FETCH_NUM)) {
        $name = $result[1];
        if ($name !== $inputs[$i]) {
            echo "Expected $inputs[$i] but got $name" . PHP_EOL;
        }
        $i++;
    }
} catch (PDOException $e) {
    echo $e->getMessage() . PHP_EOL;
} finally {
    $tsql = "DROP TABLE test1";
    $conn->exec($tsql);

    unset($stmt);
    unset($conn);
}

echo "Done";

?>

