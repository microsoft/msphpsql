--TEST--
Test another ansi encoding fr_FR euro locale outside Windows
--DESCRIPTION--
This file must be saved in ANSI encoding and the required locale must be present
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php 

require('skipif_unix_ansitests.inc'); 
$loc = setlocale(LC_ALL, 'fr_FR@euro');
$loc1 = setlocale(LC_ALL, 'fr_FR.ISO8859-15');
if (empty($loc) && empty($loc1)) {
    die("skip required French locale not available");
}

?>
--FILE--
<?php

function insertData($conn, $tableName, $inputs)
{
    $tsql = "INSERT INTO $tableName (id, phrase) VALUES (?, ?)";
    
    $param1 = null;
    $param2 = null;
    $params = array(&$param1, &$param2);  
  
    $stmt = sqlsrv_prepare($conn, $tsql, $params);
    if ($stmt === false) {
        echo "Failed to prepare the insert statement\n";
        die(print_r(sqlsrv_errors(), true));
    }
    
    for ($i = 0; $i < count($inputs); $i++) {
        $param1 = $i;
        $param2 = $inputs[$i];
        if (!sqlsrv_execute($stmt)) {
            echo "Statement could not be executed.\n";  
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

function dropTable($conn, $tableName)
{
    $tsql = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE $tableName";
    sqlsrv_query($conn, $tsql);
}

require_once('MsSetup.inc');

$tableName = "srv_ansitest_FR";
$r = setlocale(LC_ALL, 'fr_FR@euro');
if (empty($r)) {
    // Some platforms use a different locale name
    $r = setlocale(LC_ALL, 'fr_FR.ISO8859-15');
    if (empty($r)) {
        die("The required French locale is not available");
    }
}

$conn = sqlsrv_connect($server, $connectionOptions);
if( $conn === false ) {
    echo "Failed to connect\n";
    die(print_r(sqlsrv_errors(), true));
}

dropTable($conn, $tableName);

$tsql = "CREATE TABLE $tableName([id] [int] NOT NULL, [phrase] [varchar](50) NULL)";
$stmt = sqlsrv_query($conn, $tsql);

$inputs = array("À tout à l'heure!", 
                "Je suis désolé.", 
                "À plus!", 
                " Je dois aller à l'école.");

// Next, insert the strings
insertData($conn, $tableName, $inputs);

// Next, fetch the strings
$tsql = "SELECT phrase FROM $tableName ORDER by id";
$stmt = sqlsrv_query($conn, $tsql);
if ($stmt === false) {
    echo "Failed to run select query\n";
    die(print_r(sqlsrv_errors(), true));
}

$i = 0;
while (sqlsrv_fetch($stmt)) {
    $phrase = sqlsrv_get_field($stmt, 0);
    if ($phrase != $inputs[$i++]) {
        echo "Unexpected phrase retrieved:\n";
        var_dump($phrase);
    }
}

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done" . PHP_EOL;
?>
--EXPECT--
Done

