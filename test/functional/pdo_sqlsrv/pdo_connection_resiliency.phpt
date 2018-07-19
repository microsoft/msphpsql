--TEST--
Connection recovery test
--DESCRIPTION--
Connect and execute a command, kill the connection, execute another command.
Then do it again without a buffered result set, by freeing the statement before
killing the connection and then not freeing it. The latter case is the only one
that should fail. Finally, execute two queries in two threads on a recovered
non-MARS connection. This should fail too.
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc');  ?>
--FILE--
<?php
// There is a lot of repeated code here that could be refactored with helper methods,
// mostly for statement allocation. But that would affect scoping for the $stmt variables,
// which could affect the results when attempting to reconnect. What happens to statements
// when exiting the helper method? Do the associated cursors remain active? It is an
// unnecessary complication, so I have left the code like this.

require_once("break_pdo.php");
require_once("MsCommon_mid-refactor.inc");

$conn_break = connect();

///////////////////////////////////////////////////////////////////////////////
// Part 1
// Expected to successfully execute second query because buffered cursor for
// first query means connection is idle when broken
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10;";

try {
    // TODO: Idle connection resiliency does not work with Column Encryption at this point
    // Do not connect with ColumnEncryption for now
    $conn = connect($connectionInfo, array(), PDO::ERRMODE_EXCEPTION, true);
} catch (PDOException $e) {
    echo "Could not connect.\n";
    print_r($e->getMessage());
}

$query1 = "SELECT * FROM $tableName1";
try {
    $stmt1 = $conn->prepare($query1, array( PDO::ATTR_CURSOR=> PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=> PDO::SQLSRV_CURSOR_BUFFERED ));
    if ($stmt1->execute()) {
        echo "Statement 1 successful.\n";
    }

    $rowcount = $stmt1->rowCount();
    echo $rowcount." rows in result set.\n";
} catch (PDOException $e) {
    echo "Error executing statement 1.\n";
    print_r($e->getMessage());
}

breakConnection($conn, $conn_break);

$query2 = "SELECT * FROM $tableName2";
try {
    $stmt2 = $conn->prepare($query2, array( PDO::ATTR_CURSOR=> PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=> PDO::SQLSRV_CURSOR_BUFFERED ));
    if ($stmt2->execute()) {
        echo "Statement 2 successful.\n";
    }
    $rowcount = $stmt2->rowCount();
    echo $rowcount." rows in result set.\n";
} catch (PDOException $e) {
    echo "Error executing statement 2.\n";
    print_r($e->getMessage());
}

unset($conn);

///////////////////////////////////////////////////////////////////////////////
// Part 2
// Expected to successfully execute second query because first statement is
// freed before breaking connection
///////////////////////////////////////////////////////////////////////////////

try {
    $conn = connect($connectionInfo, array(), PDO::ERRMODE_EXCEPTION, true);
} catch (PDOException $e) {
    echo "Could not connect.\n";
    print_r($e->getMessage());
}

$query1 = "SELECT * FROM $tableName1";

try {
    $stmt3 = $conn->query($query1);
    if ($stmt3) {
        echo "Statement 3 successful.\n";
    }

    $rowcount = $stmt3->rowCount();
    echo $rowcount." rows in result set.\n";
} catch (PDOException $e) {
    echo "Error executing statement 3.\n";
    print_r($e->getMessage());
}

unset($stmt3);

breakConnection($conn, $conn_break);

$query2 = "SELECT * FROM $tableName2";

try {
    $stmt4 = $conn->query($query2);
    if ($stmt4) {
        echo "Statement 4 successful.\n";
    }

    $rowcount = $stmt4->rowCount();
    echo $rowcount." rows in result set.\n";
} catch (PDOException $e) {
    echo "Error executing statement 4.\n";
    print_r($e->getMessage());
}

unset($conn);

///////////////////////////////////////////////////////////////////////////////
// Part 3
// Expected to fail executing second query because default cursor for first
// query is still active when connection is broken
///////////////////////////////////////////////////////////////////////////////

try {
    $conn = connect($connectionInfo, array(), PDO::ERRMODE_EXCEPTION, true);
} catch (PDOException $e) {
    echo "Could not connect.\n";
    print_r($e->getMessage());
}

$query1 = "SELECT * FROM $tableName1";

try {
    $stmt5 = $conn->query($query1);
    if ($stmt5) {
        echo "Statement 5 successful.\n";
    }

    $rowcount = $stmt5->rowCount();
    echo $rowcount." rows in result set.\n";
} catch (PDOException $e) {
    echo "Error executing statement 5.\n";
    print_r($e->getMessage());
}

breakConnection($conn, $conn_break);

$query2 = "SELECT * FROM $tableName2";

try {
    $stmt6 = $conn->query($query2);
    if ($stmt6) {
        echo "Statement 6 successful.\n";
    }

    $rowcount = $stmt6->rowCount();
    echo $rowcount." rows in result set.\n";
} catch (PDOException $e) {
    echo "Error executing statement 6.\n";
    $err = $e->getMessage();
    if (strpos($err, 'SQLSTATE[08S02]')===false or (strpos($err, 'TCP Provider')===false and strpos($err, 'SMux Provider')===false)) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}

unset($conn);

///////////////////////////////////////////////////////////////////////////////
// Part 4
// Expected to trigger an error because there are two active statements with
// pending results and MARS is off
///////////////////////////////////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10; MultipleActiveResultSets = false;";

try {
    $conn = connect($connectionInfo, array(), PDO::ERRMODE_EXCEPTION, true);
} catch (PDOException $e) {
    echo "Could not connect.\n";
    print_r($e->getMessage());
}

breakConnection($conn, $conn_break);

try {
    $stmt7 = $conn->query("SELECT * FROM $tableName1");
    if ($stmt7) {
        echo "Statement 7 successful.\n";
    }
} catch (PDOException $e) {
    echo "Error executing statement 7.\n";
    print_r($e->getMessage());
}

try {
    $stmt8 = $conn->query("SELECT * FROM $tableName2");
    if ($stmt8) {
        echo "Statement 8 successful.\n";
    }
} catch (PDOException $e) {
    echo "Error executing statement 8.\n";
    $err = $e->getMessage();
    if (strpos($err, 'SQLSTATE[IMSSP]')===false or strpos($err, 'The connection cannot process this operation because there is a statement with pending results')===false) {
        echo "Error: Wrong error message.\n";
        print_r($err);
    }
}

unset($conn);
unset($conn_break);

?>
--EXPECT--
Statement 1 successful.
16 rows in result set.
Statement 2 successful.
9 rows in result set.
Statement 3 successful.
-1 rows in result set.
Statement 4 successful.
-1 rows in result set.
Statement 5 successful.
-1 rows in result set.
Error executing statement 6.
Statement 7 successful.
Error executing statement 8.
