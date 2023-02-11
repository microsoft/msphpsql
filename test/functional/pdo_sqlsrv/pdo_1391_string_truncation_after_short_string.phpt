--TEST--
GitHub issue 1391 - string truncation error when binding some parameters as longer strings the second time
--DESCRIPTION--
The test shows the same parameters, though bound as short strings in the first insertion, can be bound as longer strings in the subsequent insertions.
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

    dropTable($conn, 'long_strings');

$tsql = <<<CREATESQL
CREATE TABLE long_strings (
     id bigint IDENTITY(1,1) NOT NULL,
     four_thousand varchar(4002) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL,
     var_max varchar(max) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL,
     nvar_max varchar(max) NOT NULL,
     CONSTRAINT PK__long_strings__1391E83F512B1391 PRIMARY KEY (id))
CREATESQL;

    $conn->exec($tsql);

$tsql = <<<INSERTSQL
INSERT INTO long_strings (four_thousand, var_max, nvar_max) VALUES (?, ?, ?)
INSERTSQL;

    $stmt = $conn->prepare($tsql);

    // Bind and execute short string values first
    $fourThousand = '4';
    $varMax = 'v';
    $nvarMax = 'n';
    $stmt->bindParam(1, $fourThousand);
    $stmt->bindParam(2, $varMax);
    $stmt->bindParam(3, $nvarMax);
    $stmt->execute();

    // Bind and execute long string values second, on same $stmt
    $fourThousand = str_repeat('4', 4001);
    $varMax = str_repeat('v', 4001);
    $nvarMax = str_repeat('n', 4001);
    $stmt->bindParam(1, $fourThousand);
    $stmt->bindParam(2, $varMax);
    $stmt->bindParam(3, $nvarMax);
    $stmt->execute();

    // fetch the data
    $stmt = $conn->prepare("SELECT COUNT(*) FROM long_strings");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    echo $row[0]."\n";
    
    dropTable($conn, 'long_strings');

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
2
Done
