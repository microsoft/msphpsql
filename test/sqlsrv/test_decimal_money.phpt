--TEST--
make sure that decimal and money fields arent corrupted. (197725)
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require( 'MsCommon.inc' );

    $conn = Connect();
    if( $conn === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }

    $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('Products', 'U') IS NOT NULL DROP TABLE Products" );
    if( $stmt !== false ) sqlsrv_free_stmt( $stmt );

    $sql = "CREATE TABLE Products (ProductID int identity PRIMARY KEY, ProductName nvarchar(40), CategoryID int, UnitPrice money)";
    $stmt = sqlsrv_query( $conn, $sql );
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    sqlsrv_free_stmt( $stmt );
    
    $sql = "INSERT INTO Products (ProductName, CategoryID, UnitPrice) VALUES (?, ?, ?)";
 
    $productName = "TestProduct";
    $categoryId = 1;
    $unitPrice = 12.34;
 
    //each element represents a parameter
    $newProductParameters = array(
        array($productName, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(40)),
        array($categoryId, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
        array($unitPrice, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, SQLSRV_SQLTYPE_MONEY)
    );
 
    $stmt = sqlsrv_query($conn, $sql, $newProductParameters);
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    sqlsrv_free_stmt( $stmt );
    
    $stmt = sqlsrv_query( $conn, "SELECT * FROM Products" );
    while( $row = sqlsrv_fetch_array( $stmt )) {
        print_r( $row );
    }
    sqlsrv_free_stmt( $stmt );

    sqlsrv_query( $conn, "DROP TABLE Products" );
    
    sqlsrv_close( $conn );
?>
--EXPECT--
Array
(
    [0] => 1
    [ProductID] => 1
    [1] => TestProduct
    [ProductName] => TestProduct
    [2] => 1
    [CategoryID] => 1
    [3] => 12.3400
    [UnitPrice] => 12.3400
)
