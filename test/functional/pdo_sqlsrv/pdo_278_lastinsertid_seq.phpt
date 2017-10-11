--TEST--
Provide name in lastInsertId to retrieve the last sequence number
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function getNextSeq($conn, $sequenceName)
{
    $sql = "SELECT NEXT VALUE FOR $sequenceName";
    $stmt = $conn->query($sql);
    return $stmt->fetchColumn();
}

try {
    $database = "tempdb";
    $conn = connect();

    // sequence is only supported in SQL server 2012 and up (or version 11 and up)
    // Output Done once the server version is found to be < 11
    $version_arr = explode(".", $conn->getAttribute(PDO::ATTR_SERVER_VERSION));
    if ($version_arr[0] < 11) {
        echo "Done\n";
    } else {
        $tableName1 = getTableName('tab1');
        $tableName2 = getTableName('tab2');
        $sequenceName = 'sequence1';

        createTable($conn, $tableName1, array( new ColumnMeta("int", "seqnum", "NOT NULL PRIMARY KEY"), "SomeNumer" => "int"));
        createTable($conn, $tableName2, array( new ColumnMeta("int", "ID", "IDENTITY(1,2)"), "SomeValue" => "char(10)"));
        $conn->exec("IF OBJECT_ID('$sequenceName', 'SO') IS NOT NULL DROP SEQUENCE $sequenceName");
        $sql = "CREATE SEQUENCE $sequenceName AS INTEGER START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100 CYCLE";
        $conn->exec($sql);

        if (!isColEncrypted()) {
            $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 20 )");
            $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 40 )");
            $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 60 )");
        } else {
            // if column seqnum is encrypted, need to get "NEXT VALUE FOR $sequenceName" separately first and then bind param
            $sql = "INSERT INTO $tableName1 VALUES( ?, ? )";
            $stmt = $conn->prepare($sql);
            $nextSeq = getNextSeq($conn, $sequenceName);
            $stmt->execute(array($nextSeq, 20));
            $nextSeq = getNextSeq($conn, $sequenceName);
            $stmt->execute(array($nextSeq, 40));
            $nextSeq = getNextSeq($conn, $sequenceName);
            $stmt->execute(array($nextSeq, 60));
        }
        insertRow($conn, $tableName2, array("SomeValue" => "20"));

        // return the last sequence number is sequence name is provided
        $lastSeq = $conn->lastInsertId($sequenceName);
        // defaults to $tableName2 -- because it returns the last inserted row id value
        $lastRow = $conn->lastInsertId();

        if ($lastSeq == 3 && $lastRow == 1) {
            echo "Done\n";
        } else {
            echo "sequence value or identity does not match as expected\n";
        }
        dropTable($conn, $tableName1);
        dropTable($conn, $tableName2);
        $conn->exec("DROP SEQUENCE $sequenceName");
        unset($stmt);
    }
    unset($conn);
} catch (Exception $e) {
    echo "Exception $e\n";
}

?>
--EXPECT--
Done
