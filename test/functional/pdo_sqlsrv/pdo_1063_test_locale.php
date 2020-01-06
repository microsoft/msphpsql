<?php

function dropTable($conn, $tableName)
{
    $tsql = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE $tableName";
    $conn->exec($tsql);
}

function printMoney($amt) 
{
    // The money_format() function is deprecated in PHP 7.4, so use intl NumberFormatter
    $info = localeconv();
    echo "Currency symbol: " . $info['currency_symbol'] . PHP_EOL;
    echo "Thousands_sep: " . $info['thousands_sep'] . PHP_EOL;

    $loc = setlocale(LC_MONETARY, 0);
    $symbol = $info['int_curr_symbol'];

    echo "Amount formatted: ";
    if (empty($symbol)) {
        echo number_format($amt, 2, '.', '');
    } else {
        $fmt = new NumberFormatter($loc, NumberFormatter::CURRENCY);
        $fmt->setTextAttribute(NumberFormatter::CURRENCY_CODE, $symbol);
        $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        echo $fmt->format($amt);
    }
    echo PHP_EOL;
}

// This test is invoked by pdo_1063_locale_configs.phpt
require_once('MsSetup.inc');

$locale = ($_SERVER['argv'][1] ?? '');

echo "**Begin**" . PHP_EOL;
echo "Current LC_MONETARY: " . setlocale(LC_MONETARY, 0) . PHP_EOL;
echo "Current LC_CTYPE: " . setlocale(LC_CTYPE, 0) . PHP_EOL;

if (!empty($locale)) {
    $loc = setlocale(LC_ALL, $locale);
    echo "Setting LC_ALL: " . $loc . PHP_EOL;
}

$n1 = 10000.98765;
printMoney($n1);

echo strftime("%A", strtotime("12/25/2020")) . PHP_EOL;
echo strftime("%B", strtotime("12/25/2020")) . PHP_EOL;

try {
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; driver=$driver", $uid, $pwd );

    $tableName = "[" . "pdo1063" . $locale . "]";
    
    dropTable($conn, $tableName);

    $pi = "3.14159";

    $stmt = $conn->query("CREATE TABLE $tableName (c1 FLOAT)");
    $stmt = $conn->query("INSERT INTO $tableName (c1) VALUES ($pi)");

    $sql = "SELECT c1 FROM $tableName";
    $stmt = $conn->prepare($sql, array(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true));
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_NUM);
    echo ($row[0]) . PHP_EOL;
    unset($stmt);

    dropTable($conn, $tableName);

    unset($conn);
} catch( PDOException $e ) {
    print_r( $e->getMessage() );
}

echo "**End**" . PHP_EOL;
?>