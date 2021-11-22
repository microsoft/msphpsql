--TEST--
GitHub issue 1329 - string truncation error when binding some parameters as non-nulls the second time
--DESCRIPTION--
The test shows the same parameters, though bound as nulls in the first insertion, can be bound as non-nulls in the subsequent insertions.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once("MsSetup.inc");

function dropTable($conn, $tableName)
{
    $drop = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE $tableName";
    $conn->exec($drop);
}

try {
    $conn = new PDO("sqlsrv:server=$server; Database = $databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    dropTable($conn, 'domains');

$tsql = <<<CREATESQL
CREATE TABLE domains (
     id bigint IDENTITY(1,1) NOT NULL,
     authority nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL,
     base_url_redirect nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
     regular_not_found_redirect nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
     invalid_short_url_redirect nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
     CONSTRAINT PK__domains__3213E83F512B36BA PRIMARY KEY (id))
CREATESQL;

    $conn->exec($tsql);

$tsql = <<<INSERTSQL
INSERT INTO domains (authority, base_url_redirect, regular_not_found_redirect, invalid_short_url_redirect) VALUES (?, ?, ?, ?)
INSERTSQL;

    $stmt = $conn->prepare($tsql);
    $authority = 'foo.com';
    $base = null;
    $notFound = null;
    $invalid = null;
    $stmt->bindParam(1, $authority);
    $stmt->bindParam(2, $base);
    $stmt->bindParam(3, $notFound);
    $stmt->bindParam(4, $invalid);
    $stmt->execute();

    $authority = 'detached-with-ředirects.com';
    $base = 'fŏő.com';
    $notFound = 'baŗ.com';
    $invalid = null;
    $stmt->bindParam(1, $authority);
    $stmt->bindParam(2, $base);
    $stmt->bindParam(3, $notFound);
    $stmt->bindParam(4, $invalid);
    $stmt->execute();
    
    $authority = 'Őther-redirects.com';
    $base = 'fooš.com';
    $notFound = null;
    $invalid = 'ŷëå';
    $stmt->bindParam(1, $authority);
    $stmt->bindParam(2, $base);
    $stmt->bindParam(3, $notFound);
    $stmt->bindParam(4, $invalid);
    $stmt->execute();

    // fetch the data
    $stmt = $conn->prepare("SELECT * FROM domains");
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_NUM);
    print_r($row);
    
    dropTable($conn, 'domains');

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => 1
            [1] => foo.com
            [2] => 
            [3] => 
            [4] => 
        )

    [1] => Array
        (
            [0] => 2
            [1] => detached-with-ředirects.com
            [2] => fŏő.com
            [3] => baŗ.com
            [4] => 
        )

    [2] => Array
        (
            [0] => 3
            [1] => Őther-redirects.com
            [2] => fooš.com
            [3] => 
            [4] => ŷëå
        )

)
Done
