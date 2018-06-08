--TEST--
Bind parameters using an array
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Connect
    $conn = connect();

    // Create table
    $tableName = 'bindParams';
    dropTable($conn, $tableName);
    
    $sql = "CREATE TABLE $tableName (ID TINYINT, SID CHAR(5))";
    $stmt = $conn->exec($sql);

    createTable($conn, $tableName, array("ID" => "tinyint", "SID" => "char(5)"));

    // Insert data using bind parameters
    $sql = "INSERT INTO $tableName VALUES (?,?)";
    for ($t=100; $t<103; $t++) {
        $stmt = $conn->prepare($sql);
        $ts = substr(sha1($t),0,5);
        $params = array($t,$ts);
        $stmt->execute($params);
    }

    // Query, but do not fetch
    $sql = "SELECT * from $tableName";
    $stmt = $conn->query($sql);

    // Insert duplicate row, ID = 100
    $t = 100;
    insertRow($conn, $tableName, array( "ID" => $t, "SID" => substr( sha1( $t ), 0, 5 )), "prepareExecuteBind");

    // Fetch. The result set should not contain duplicates
    $data = $stmt->fetchAll();
    foreach ($data as $a)
        echo $a['ID'] . "|" . $a['SID'] . "\n";

    // Close connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);

    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
100|310b8
101|dbc0f
102|c8306
Done
