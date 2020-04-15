--TEST--
Test sqlsrv_commit method with logging
--DESCRIPTION--
Similar to sqlsrv_commit.phpt but also test some basic logging activities
By adding integer values together, we can specify more than one logging option at a time.
SQLSRV_LOG_SYSTEM_CONN (2) Turns on logging of connection activity.
SQLSRV_LOG_SYSTEM_STMT (4) Turns on logging of statement activity.

For example, sqlsrv.LogSubsystems = 6
turns on logging of connection and statement activities
--SKIPIF--
<?php require_once('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_NOTICE);
    sqlsrv_configure('LogSubsystems', 6);

    require_once('MsCommon.inc');

    $conn = connect();
    if (!$conn) {
        fatalError("Could not connect");
    }

    $stmt1 = sqlsrv_query($conn, "IF OBJECT_ID('Products', 'U') IS NOT NULL DROP TABLE Products");
    $stmt1 = sqlsrv_query($conn, "CREATE TABLE Products (ProductID int PRIMARY KEY, ProductName nvarchar(40), CategoryID int, UnitPrice money)");
    if ($stmt1 === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt1);

    $stmt2 = sqlsrv_query($conn, "INSERT INTO Products (ProductID, ProductName, CategoryID, UnitPrice) VALUES (1, 'TestProduct2', 2, '13.55')");
    $stmt3 = sqlsrv_query($conn, "SELECT * FROM Products WHERE CategoryID = 2");

    if ($stmt2 && $stmt3) {
        sqlsrv_commit($conn);
    }

    $stmt1 = sqlsrv_query($conn, "DROP TABLE Products");
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_free_stmt($stmt3);

    sqlsrv_close($conn);
?>
--EXPECT--
sqlsrv_connect: entering
sqlsrv_query: entering
sqlsrv_query: entering
sqlsrv_stmt_dtor: entering
sqlsrv_free_stmt: entering
sqlsrv_stmt_dtor: entering
sqlsrv_query: entering
sqlsrv_query: entering
sqlsrv_commit: entering
sqlsrv_query: entering
sqlsrv_free_stmt: entering
sqlsrv_stmt_dtor: entering
sqlsrv_free_stmt: entering
sqlsrv_stmt_dtor: entering
sqlsrv_free_stmt: entering
sqlsrv_stmt_dtor: entering
sqlsrv_close: entering
