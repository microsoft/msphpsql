--TEST--
testing the quote method with different inputs and then test with a empty query
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    $output1 = $conn->quote("1'2'3'4'5'6'7'8", PDO::PARAM_INT);
    var_dump($output1);

    $output2 = $conn->quote("{ABCD}'{EFGH}", PDO::PARAM_STR);
    var_dump($output2);

    $output3 = $conn->quote("<XmlTestData><Letters>The quick brown fox jumps over the lazy dog</Letters><Digits>0123456789</Digits></XmlTestData>");
    var_dump($output3);

    $stmt = $conn->query("");
    if ($stmt != false) {
        echo("Empty query was expected to fail!\n");
    }
    unset($stmt);

    $stmt1 = $conn->prepare($output2);
    $result = $stmt1->execute();
    if ($result != false) {
        echo("This query was expected to fail!\n");
    }
    unset($stmt1);

    $stmt2 = $conn->query($output3);
    if ($stmt2 != false) {
        echo("This query was expected to fail!\n");
    }
    unset($stmt2);

    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
string(24) "'1''2''3''4''5''6''7''8'"
string(16) "'{ABCD}''{EFGH}'"
string(118) "'<XmlTestData><Letters>The quick brown fox jumps over the lazy dog</Letters><Digits>0123456789</Digits></XmlTestData>'"
