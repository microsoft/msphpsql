--TEST--
GitHub issue #678 - Idle Connection Resiliency doesn't work with Connection Pooling
--DESCRIPTION--
Verifies that the issue has been fixed with ODBC 17.1
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkODBCVersion($conn)
{
    $msodbcsql_ver = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
    $vers = explode(".", $msodbcsql_ver);

    if ($vers[0] >= 17 && $vers[1] > 0){
        return true;
    } else {
        return false;
    }
}

function breakConnection($conn, $conn_break)
{
    try {
        $stmt1 = $conn->query("SELECT @@SPID");
        $obj = $stmt1->fetch(PDO::FETCH_NUM);
        $spid = $obj[0];

        $stmt2 = $conn_break->prepare("KILL $spid");
        $stmt2->execute();
        sleep(1);
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
}

// create a connection for create the table and breaking other connections
$conn_break = connect();

if (! checkODBCVersion($conn_break)) {
    echo "Done\n";
    return;
}

$tableName = "test_connres";
dropTable($conn_break, $tableName);

try {

    $sql = "CREATE TABLE $tableName (c1 INT, c2 VARCHAR(40))";
    $stmt = $conn_break->query($sql);

    $sql = "INSERT INTO $tableName VALUES (?, ?)";
    $stmt = $conn_break->prepare($sql);
    for ($t = 200; $t < 209; $t++) {
        $ts = substr(sha1($t), 0, 5);
        $stmt->bindValue(1, $t);
        $stmt->bindValue(2, $ts);
        $stmt->execute();
    }
} catch (PDOException $e) {
    echo "Could not connect.\n";
    print_r($e->getMessage());
}

// first connection
$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10; ConnectionPooling = 1;";
try {
    $conn = connect($connectionInfo, array(), PDO::ERRMODE_EXCEPTION, true);
} catch (PDOException $e) {
    echo "Error in connection 1.\n";
    print_r($e->getMessage());
}

breakConnection($conn, $conn_break);

$query = "SELECT * FROM $tableName";
try {
    $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    if (!$stmt || !$stmt->execute()) {
        echo "Statement 1 failed.\n";
    }
    
    $row_count = $stmt->rowCount();
    if ($row_count != 9) {
        echo "Unexpected $row_count rows in result set.\n";
    }
} catch (PDOException $e) {
    echo "Error executing query with connection 1.\n";
    print_r($e->getMessage());
}

unset($stmt);
unset($conn);

// second connection
try {
    $conn = connect($connectionInfo, array(), PDO::ERRMODE_EXCEPTION, true);
} catch (PDOException $e) {
    echo "Error in connection 2.\n";
    print_r($e->getMessage());
}

breakConnection($conn, $conn_break);

// would connection be able to resume here if connection pooling is enabled?
try {
    $stmt2 = $conn->query($query);
    if (!$stmt2) {
        echo "Statement 2 failed.\n";
    }

    $col_count = $stmt2->columnCount();
    if ($col_count != 2) {
        echo "Unexpected $col_count columns in result set.\n";
    }
} catch (PDOException $e) {
    echo "Error executing query with connection 2.\n";
    print_r($e->getMessage());
}

dropTable($conn, $tableName);

echo "Done\n";

unset($stmt2);
unset($conn);
unset($conn_break);

?>
--EXPECT--
Done