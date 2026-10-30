--TEST--
Bound AE fixtures do not require or select an ANSI command-text locale
--DESCRIPTION--
Checks prerequisite behavior without connecting or requiring an encryption key.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('MsCommon.inc');
$originalLocale = setlocale(LC_CTYPE, 0);
$keystore = AE\KEYSTORE_WIN;
foreach ([false, true] as $dataEncrypted) {
    foreach ([false, true] as $localeDisabled) {
        require('skipif_ansi_ae_fixture.inc');
        if (setlocale(LC_CTYPE, 0) !== $originalLocale) {
            throw new RuntimeException('Bound fixture prerequisite changed the locale');
        }
    }
}
echo "Bound fixture paths remain enabled without changing the locale\n";
?>
--EXPECT--
Bound fixture paths remain enabled without changing the locale
