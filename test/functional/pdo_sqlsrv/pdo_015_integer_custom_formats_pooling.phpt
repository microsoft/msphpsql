--TEST--
Number MAX_INT to string with custom formats, see pdo_014. Pooling enabled.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $pooling = true;

    /* Sample number MAX_INT */
    $sample = 2*(2**30-1)+1;

    /* Connect + create a new pool */
    $conn0 = connect("ConnectionPooling=$pooling");
    $conn0->query("select 1");
    unset($conn0);

    /* Connect */
    $conn = connect("ConnectionPooling=$pooling");

    // Create a temporary table
    $tableName = 'testFormats';
    createTable($conn, $tableName, array("col1" => "int"));

    // Query number with custom format
    $query = "SELECT CAST($sample as varchar) + '.00'";
    $stmt = $conn->query($query);
    $data = $stmt->fetchColumn();
    var_dump($data);

    // Insert data using bind parameters
    $query = "INSERT INTO $tableName VALUES(:p0)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':p0', $sample, PDO::PARAM_INT);
    $stmt->execute();

    // Fetching. Prepare with client buffered cursor
    if (!isColEncrypted()) {
        $query = "SELECT TOP 1 cast(col1 as varchar) + '.00 EUR' FROM $tableName";
    } else {
        // cannot explicitly cast data to another type from an encrypted column
        $query = "SELECT TOP 1 col1 FROM $tableName";
    }
        
    $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->execute();
    $value = $stmt->fetchColumn();
    if (isColEncrypted()) {
        $value .= ".00 EUR";
    }
    var_dump($value);

    //Free the statement and connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);

    print "Done";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
string(13) "2147483647.00"
string(17) "2147483647.00 EUR"
Done
