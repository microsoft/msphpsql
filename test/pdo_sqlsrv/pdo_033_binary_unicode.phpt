--TEST--
Insert binary HEX data then fetch it back as string
--DESCRIPTION--
Insert binary HEX data into an nvarchar field then read it back as UTF-8 string 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
try
{
    require_once("MsSetup.inc");

    // Connect
    $conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd);

    // Create table
    $tableName = '#pdo_033test';
    $sql = "CREATE TABLE $tableName (c1 NVARCHAR(100))";
    $stmt = $conn->exec($sql);

    $input = pack( "H*", '49006427500048005000' );  // I'LOVE_SYMBOL'PHP

    $stmt = $conn->prepare("INSERT INTO $tableName (c1) VALUES (?)");
    $stmt->bindParam(1, $input, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
    $result = $stmt->execute();
    if (! $result)
        echo "Failed to insert!\n";

    $stmt = $conn->query("SELECT * FROM $tableName");
    $utf8 = $stmt->fetchColumn();

    echo "\n". $utf8 ."\n";

    $stmt = null;
    $conn = null;
}
catch (Exception $e)
{
    echo $e->getMessage();
}
    
print "Done";

?>

--EXPECT--
I❤PHP
Done
