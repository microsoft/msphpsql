--TEST--
Moves the cursor to the next result set
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    /* Connect */
    $conn = connect();

    // Create table
    $tableName = 'testResultSet';
    createTable($conn, $tableName, array("c1" => "int", "c2" => "varchar(40)"));

    // Insert data using bind parameters
    $sql = "INSERT INTO $tableName VALUES (?,?)";
    $inputs = array();
    for ($t=200; $t<220; $t++) {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $t);
        $ts = sha1($t);
        $stmt->bindParam(2, $ts);
        $stmt->execute();
        array_push($inputs, array($t, $ts));
    }

    // Fetch, get data and move the cursor to the next result set
    if (!isColEncrypted()) {
        $sql = "SELECT * from $tableName WHERE c1 = '204' OR c1 = '210';
                SELECT Top 3 * FROM $tableName ORDER BY c1 DESC";
        $stmt = $conn->query($sql);
        $expected = array(array(219, sha1(219)), array(218, sha1(218)), array(217, sha1(217)));
    } else {
        // ORDER BY does not work for encrypted columns. In
        //https://docs.microsoft.com/en-us/sql/relational-databases/security/encryption/always-encrypted-database-engine,
        //the Feature Details section states that operators such as greater/less than does not work for encrypted columns, and ORDER BY is based on that
        $sql = "SELECT * FROM $tableName WHERE c1 = ? OR c1 = ?;
                SELECT Top 3 * FROM $tableName";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('204', '210'));
    }
    $data1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->nextRowset();
    $data2 = $stmt->fetchAll(PDO::FETCH_NUM);

    // Array: FETCH_ASSOC
    foreach ($data1 as $a) {
        echo $a['c1'] . "|" . $a['c2'] . "\n";
    }

    // Array: FETCH_NUM
    if (!isColEncrypted()) {
        $i = 0;
        foreach ($data2 as $a) {
            if ($expected[$i][0] != $a[0] || $expected[$i][1] != $a[1]) {
                echo "Values in row $i does not match the expected output.\n";
            }
            $i++;
        }
    } else {
        // don't know the order of the result set; simply compare to see if the result set is in $inputs
        foreach ($data2 as $a) {
            $match = false;
            foreach ($inputs as $input) {
                if ($a[0] == $input[0] && $a[1] == $input[1]) {
                    $match = true;
                }
            }
            if (!$match) {
                echo "Value fetched for $a[0] is incorrect.\n";
            }
        }
    }

    // Close connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($con);

    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
204|1cc641954099c249e0e4ef0402da3fd0364d95f0
210|135debd4837026bf06c7bfc5d1e0c6a31611af1d
Done
