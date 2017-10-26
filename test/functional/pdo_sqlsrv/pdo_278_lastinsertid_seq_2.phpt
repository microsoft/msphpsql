--TEST--
LastInsertId returns the last sequences operating on the same table
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
    $conn = connect();

    // sequence is only supported in SQL server 2012 and up (or version 11 and up)
    // Output Done once the server version is found to be < 11
    $version_arr = explode(".", $conn->getAttribute(PDO::ATTR_SERVER_VERSION));
    if ($version_arr[0] < 11) {
        echo "Done\n";
    } else {
        $tableName = getTableName('tab');
        $sequence1 = 'sequence1';
        $sequence2 = 'sequenceNeg1';
        createTable($conn, $tableName, array( new ColumnMeta("int", "ID", "IDENTITY(1,1)"), new ColumnMeta("int", "SeqNumInc", "NOT NULL PRIMARY KEY"), "SomeNumber" => "int"));
        $conn->exec("IF OBJECT_ID('$sequence1', 'SO') IS NOT NULL DROP SEQUENCE $sequence1");
        $conn->exec("IF OBJECT_ID('$sequence2', 'SO') IS NOT NULL DROP SEQUENCE $sequence2");
        $sql = "CREATE SEQUENCE $sequence1 AS INTEGER START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100";
        $stmt = $conn->exec($sql);
        $sql = "CREATE SEQUENCE $sequence2 AS INTEGER START WITH 200 INCREMENT BY -1 MINVALUE 101 MAXVALUE 200";
        $stmt = $conn->exec($sql);

        if (!isColEncrypted()) {
            $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence1, 20 )");
            $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence2, 180 )");
            $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence1, 40 )");
            $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence2, 160 )");
            $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence1, 60 )");
            $ret = $conn->exec("INSERT INTO $tableName VALUES( NEXT VALUE FOR $sequence2, 140 )");
        } else {
            // if column seqnum is encrypted, need to get "NEXT VALUE FOR $sequenceName" separately first and then bind param
            $sql = "INSERT INTO $tableName VALUES( ?, ? )";
            $stmt = $conn->prepare($sql);
            $nextSeq1 = getNextSeq($conn, $sequence1);
            $stmt->execute(array( $nextSeq1, 20 ));
            $nextSeq2 = getNextSeq($conn, $sequence2);
            $stmt->execute(array( $nextSeq2, 180 ));
            $nextSeq1 = getNextSeq($conn, $sequence1);
            $stmt->execute(array( $nextSeq1, 40 ));
            $nextSeq2 = getNextSeq($conn, $sequence2);
            $stmt->execute(array( $nextSeq2, 160 ));
            $nextSeq1 = getNextSeq($conn, $sequence1);
            $stmt->execute(array( $nextSeq1, 60 ));
            $nextSeq2 = getNextSeq($conn, $sequence2);
            $stmt->execute(array( $nextSeq2, 140 ));
        }
        // return the last sequence number of 'sequence1'
        $lastSeq1 = $conn->lastInsertId($sequence1);

        // return the last sequence number of 'sequenceNeg1'
        $lastSeq2 = $conn->lastInsertId($sequence2);

        // providing a table name in lastInsertId should return an empty string
        $lastSeq3 = $conn->lastInsertId($tableName);

        if ($lastSeq1 == 3 && $lastSeq2 == 198 && $lastSeq3 == "") {
            echo "Done\n";
        }

        dropTable($conn, $tableName);
        $stmt = $conn->exec("DROP SEQUENCE $sequence1");
        $stmt = $conn->exec("DROP SEQUENCE $sequence2");
        unset($stmt);
    }
    unset($conn);
} catch (Exception $e) {
    echo "Exception $e\n";
}

?>
--EXPECT--
Done
