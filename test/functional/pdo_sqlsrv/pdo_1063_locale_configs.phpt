--TEST--
GitHub issue 1063 - make setting locale info configurable
--DESCRIPTION--
This test verifies that the users can configure using ini file to set application locale using the system locale or not. This test is valid for Linux and macOS systems only.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_unix_locales.inc'); ?>
--FILE--
<?php
function runTest($val, $file, $locale)
{
    print("\n***sqlsrv.SetLocaleInfo = $val\npdo_sqlsrv.set_locale_info = $val***\n\n");
    shell_exec("echo 'sqlsrv.SetLocaleInfo = $val\npdo_sqlsrv.set_locale_info = $val' > $file");
    print_r(shell_exec(PHP_BINARY." ".dirname(__FILE__)."/pdo_1063_test_locale.php "));
    print_r(shell_exec(PHP_BINARY." ".dirname(__FILE__)."/pdo_1063_test_locale.php $locale"));
}

$inifile = PHP_CONFIG_FILE_SCAN_DIR."/99-overrides.ini";

$locale1 = strtoupper(PHP_OS) === 'LINUX' ? "en_US.ISO-8859-1" : "en_US.ISO8859-1";
$locale2 = 'de_DE.UTF-8';

runTest(0, $inifile, $locale1);
runTest(1, $inifile, $locale2);
runTest(2, $inifile, $locale2);
?>
--EXPECT--

***sqlsrv.SetLocaleInfo = 0
pdo_sqlsrv.set_locale_info = 0***

**Begin**
Current LC_MONETARY: C
Current LC_CTYPE: en_US.UTF-8
Currency symbol: 
Thousands_sep: 
Amount formatted: 10000.99
Friday
December
3.14159
**End**
**Begin**
Current LC_MONETARY: C
Current LC_CTYPE: en_US.UTF-8
Setting LC_ALL: en_US.ISO-8859-1
Currency symbol: $
Thousands_sep: ,
Amount formatted: USD 10,000.99
Friday
December
3.14159
**End**

***sqlsrv.SetLocaleInfo = 1
pdo_sqlsrv.set_locale_info = 1***

**Begin**
Current LC_MONETARY: C
Current LC_CTYPE: en_US.UTF-8
Currency symbol: 
Thousands_sep: 
Amount formatted: 10000.99
Friday
December
3.14159
**End**
**Begin**
Current LC_MONETARY: C
Current LC_CTYPE: en_US.UTF-8
Setting LC_ALL: de_DE.UTF-8
Currency symbol: €
Thousands_sep: .
Amount formatted: 10.000,99 EUR
Freitag
Dezember
3,14159
**End**

***sqlsrv.SetLocaleInfo = 2
pdo_sqlsrv.set_locale_info = 2***

**Begin**
Current LC_MONETARY: en_US.UTF-8
Current LC_CTYPE: en_US.UTF-8
Currency symbol: $
Thousands_sep: ,
Amount formatted: USD 10,000.99
Friday
December
3.14159
**End**
**Begin**
Current LC_MONETARY: en_US.UTF-8
Current LC_CTYPE: en_US.UTF-8
Setting LC_ALL: de_DE.UTF-8
Currency symbol: €
Thousands_sep: .
Amount formatted: 10.000,99 EUR
Freitag
Dezember
3,14159
**End**
