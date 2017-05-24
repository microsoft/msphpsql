--TEST--
testing the quote method with different inputs and then test with a empty query
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function Quote()
{
    require("MsSetup.inc");
    
    $conn = new PDO( "sqlsrv:server=$server; database=$databaseName", $uid, $pwd);
    
    $output1 = $conn->quote("1'2'3'4'5'6'7'8", PDO::PARAM_INT);
    var_dump($output1);
    
    $output2 = $conn->quote("{ABCD}'{EFGH}", PDO::PARAM_STR);
    var_dump($output2);
    
    $output3 = $conn->quote("<XmlTestData><Letters>The quick brown fox jumps over the lazy dog</Letters><Digits>0123456789</Digits></XmlTestData>");	
    var_dump($output3);   

    $stmt = $conn->query("");
    if ($stmt != false)
    {
        echo("Empty query was expected to fail!\n");
    }    
    
    $stmt1 = $conn->prepare($output2);
    $result = $stmt1->execute();
    if ($result != false)
    {
        echo("This query was expected to fail!\n");
    }
    $stmt1 = null;
    
    $stmt2 = $conn->query($output3);
    if ($stmt2 != false)
    {
        echo("This query was expected to fail!\n");
    }    
    
    $conn = null;   
}

function Repro()
{
    StartTest("pdo_connection_quote");
    echo "\nStarting test...\n";
    try
    {
        Quote();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_connection_quote");
}

Repro();

?>
--EXPECT--

Starting test...
string(24) "'1''2''3''4''5''6''7''8'"
string(16) "'{ABCD}''{EFGH}'"
string(118) "'<XmlTestData><Letters>The quick brown fox jumps over the lazy dog</Letters><Digits>0123456789</Digits></XmlTestData>'"

Done
Test "pdo_connection_quote" completed successfully.
