--TEST--
Test sqlsrv_commit method.
--SKIPIF--
<?php require_once('skipif.inc'); ?>
--FILE--
<?php
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
        echo "Commit successful";
    }

    $stmt1 = sqlsrv_query($conn, "DROP TABLE Products");
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_free_stmt($stmt3);

?>
--EXPECT--
Commit successful
