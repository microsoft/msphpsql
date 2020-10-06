<?php
// This test is invoked by pdo_1063_locale_configs.phpt

function dropTable($conn, $tableName)
{
    $tsql = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE $tableName";
    $conn->exec($tsql);
}

function printMoney($amt, $info) 
{
    // The money_format() function is deprecated in PHP 7.4, so use intl NumberFormatter
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

require_once('MsSetup.inc');

$setLocaleInfo = ($_SERVER['argv'][1]);
$locale = ($_SERVER['argv'][2] ?? '');

echo "**Begin**" . PHP_EOL;

// Assuming LC_ALL is 'en_US.UTF-8', so is LC_CTYPE, except in PHP 8 (TODO)
// But default LC_MONETARY varies
$ctype = (PHP_MAJOR_VERSION == 8 && $setLocaleInfo == 0) ? 'C' : 'en_US.UTF-8';
switch ($setLocaleInfo) {
    case 0:
    case 1:
        $m = 'C'; $symbol = ''; $sep = '';
        break;
    case 2:
        $m = 'en_US.UTF-8'; $symbol = '$'; $sep = ',';
        break;
    default:
        die("Unexpected $setLocaleInfo\n");
        break;
}

$m1 = setlocale(LC_MONETARY, 0);
if ($m !== $m1) {
    echo "Unexpected LC_MONETARY: $m1" . PHP_EOL;
}
$c1 = setlocale(LC_CTYPE, 0);
if ($ctype !== $c1) {
    echo "Unexpected LC_CTYPE: $c1" . PHP_EOL;
    echo "LC_NUMERIC for $setLocaleInfo: " . setlocale(LC_NUMERIC, 0) . PHP_EOL;
}

// Set a different locale, if the input is not empty
$english = true;
if (!empty($locale)) {
    $loc = setlocale(LC_ALL, $locale);
    if ($loc !== $locale) {
        echo "Unexpected $loc for LC_ALL " . PHP_EOL;
    }
    
    // Currency symbol and thousands separator in Linux and macOS may be different
    if ($loc === 'de_DE.UTF-8') {
        $symbol = strtoupper(PHP_OS) === 'LINUX' ? '€' : 'Eu';
        $sep = strtoupper(PHP_OS) === 'LINUX' ? '.' : '';
        $english = false;
    } else {
        $symbol = '$';
        $sep = ',';
    }
}

$info = localeconv();
if ($symbol !== $info['currency_symbol']) {
    echo "$locale: Expected currency symbol '$symbol' but get '" . $info['currency_symbol'] . "'";
    echo PHP_EOL;
}
if ($sep !== $info['thousands_sep']) {
    echo "$locale: Expected thousands separator '$sep' but get '" . $info['currency_symbol'] . "'";
    echo PHP_EOL;
}

$n1 = 10000.98765;
printMoney($n1, $info);

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
    
    // The following change is required for the breaking change introduced in PHP 8
    // https://wiki.php.net/rfc/locale_independent_float_to_string
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $value = $row[0];
    $expected = 3.14159;
    if (PHP_MAJOR_VERSION < 8) {
        if ($setLocaleInfo > 0 && $english === false) {
            $expected = floatval($pi);
        }
    }
    if ($value != $expected) {
        echo "Expected: '$expected' but got '$value'\n";
    }

    unset($stmt);

    dropTable($conn, $tableName);

    unset($conn);
} catch( PDOException $e ) {
    print_r( $e->getMessage() );
}

echo "**End**" . PHP_EOL;
?>