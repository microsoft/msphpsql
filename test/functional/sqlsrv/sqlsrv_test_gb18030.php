<?php
/*
This ansi test is invoked by sqlsrv_ansi_locale_zh.phpt
*/
function insertText($conn, $text, $hexValue)
{
    $hex = bin2hex($text);
    if ($hex !== $hexValue) {
        "Expected $hexValue but got $hex" . PHP_EOL;
    }
    $tsql = "INSERT INTO test1 VALUES (?)";
    $params = array($text);

    $stmt = sqlsrv_query($conn, $tsql, $params);
    if ($stmt === false) {
        var_dump(sqlsrv_errors());
    }
}

require_once('MsSetup.inc');

$tempDB = ($_SERVER['argv'][1]);

setlocale(LC_ALL, 'zh_CN.gb18030');

$options = array("Database"=>$tempDB, "UID"=>$userName, "PWD"=>$userPassword);
$conn = sqlsrv_connect($server, $options);
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$tsql = "CREATE TABLE test1([id] int identity, [name] [varchar](50) NULL)";
$stmt = sqlsrv_query($conn, $tsql);

// Next, insert the strings
$inputs = array('中文', '你好', '未找到信息', '获取更多');
$hexValues = array('d6d0cec4', 'c4e3bac3', 'ceb4d5d2b5bdd0c5cfa2', 'bbf1c8a1b8fcb6e0');
for ($i = 0; $i < 4; $i++) {
    insertText($conn, $inputs[$i], $hexValues[$i]);
}

// Next, fetch the strings
$tsql = "SELECT * FROM test1";
$stmt = sqlsrv_query($conn, $tsql);
if ($stmt === false) {
    var_dump(sqlsrv_errors());
}

$i = 0;
while (sqlsrv_fetch($stmt)) {
    $name = sqlsrv_get_field($stmt, 1); 
    if ($name !== $inputs[$i]) {
        echo "Expected $inputs[$i] but got $name" . PHP_EOL;
    }
    $i++;
}

$tsql = "DROP TABLE test1";
sqlsrv_query($conn, $tsql);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done";

?>

