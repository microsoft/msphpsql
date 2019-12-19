<?php

// This test is invoked by srv_1063_locale_configs.phpt
require_once('MsCommon.inc');

$locale = ($_SERVER['argv'][1] ?? '');

echo "**Begin**" . PHP_EOL;
echo "Current LC_MONETARY: " . setlocale(LC_MONETARY, 0) . PHP_EOL;
echo "Current LC_CTYPE: " . setlocale(LC_CTYPE, 0) . PHP_EOL;

if (!empty($locale)) {
    $loc = setlocale(LC_ALL, $locale);
    echo "Setting LC_ALL: " . $loc . PHP_EOL;
}

$info = localeconv();
echo "Currency symbol: " . $info['currency_symbol'] . PHP_EOL;
echo "Thousands_sep: " . $info['thousands_sep'] . PHP_EOL;

$n1 = 10000.98765;
echo "Amount formatted: " . money_format("%i", $n1) . PHP_EOL;
echo strftime("%A", strtotime("12/25/2020")) . PHP_EOL;
echo strftime("%B", strtotime("12/25/2020")) . PHP_EOL;

$conn = connect();

$tableName = "[" . "srv1063" . $locale . "]";
dropTable($conn, $tableName);

$pi = "3.14159";

$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (c1 FLOAT)");
if (!$stmt) {
    fatalError("Failed to create test table $tableName");
}
$stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1) VALUES ($pi)");
if (!$stmt) {
    fatalError("Failed to insert into test table $tableName");
}

$sql = "SELECT c1 FROM $tableName";
$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
    fatalError("Failed in running query $sql");
}

while (sqlsrv_fetch($stmt)) {
   $value = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_FLOAT);
   echo $value . PHP_EOL;
}

sqlsrv_free_stmt($stmt);

dropTable($conn, $tableName);

sqlsrv_close($conn);

echo "**End**" . PHP_EOL;
?>