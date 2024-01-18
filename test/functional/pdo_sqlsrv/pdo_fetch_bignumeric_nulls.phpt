--TEST--
Test attribute PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE for bigint, decimal and numeric types with null values
--DESCRIPTION--
Test attribute PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE for different bigint, decimal and numeric
types with null values. Whether retrieved as strings, ints or floats should return NULLs.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkNull($row, $columns, $fetchStyle) 
{
    $size = count($columns);
    for ($i = 0; $i < $size; $i++) {
        $col = $columns[$i];
        if ($fetchStyle == PDO::FETCH_ASSOC) {
            $dtObj = $row[$col];
        } else {
            // assume PDO::FETCH_BOTH
            $dtObj = $row[$i];
        }
        if (!is_null($dtObj)) {
            echo "Expected NULL for column $col but got: ";
            var_dump($dtObj);
        } 
    }
}

function runTest($conn, $query, $columns, $useBuffer = false)
{
    // fetch the date time values as strings or date time objects
    // prepare with or without buffered cursor 
    $options = array();
    if ($useBuffer) {
        $options = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, 
                         PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED);
    }
    
    // fetch_bignumeric off 
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, false);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkNull($row, $columns, PDO::FETCH_NUM);

    // fetch_bignumeric on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkNull($row, $columns, PDO::FETCH_ASSOC);
    
    // conn attribute fetch_bignumeric on, but statement attribute fetch_bignumeric off --
    // expected strings to be returned because statement attribute overrides the 
    // connection attribute
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, false);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkNull($row, $columns, PDO::FETCH_NUM);
    
    // conn attribute fetch_bignumeric unchanged, but statement attribute fetch_bignumeric on --
    // expected datetime objects to be returned (this time no need to prepare the statement)
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, true);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkNull($row, $columns, PDO::FETCH_ASSOC);

    // likewise, conn attribute fetch_bignumeric off, but statement attribute 
    // fetch_bignumeric on -- expected int/float objects to be returned
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, false);
    $stmt = $conn->prepare($query, $options);
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, true);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_BOTH);
    checkNull($row, $columns, PDO::FETCH_BOTH);
    
    // conn attribute fetch_bignumeric unchanged, but statement attribute fetch_bignumeric off --
    // expected strings to be returned (again no need to prepare the statement)
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, false);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkNull($row, $columns, PDO::FETCH_NUM);
    
    // last test: set statement attribute fetch_datetime on with no change to 
    // prepared statement -- expected int/float objects to be returned
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_BIGNUMERIC_TYPE, true);
    $i = 0;
    do {
        $stmt->execute();
        $dtObj = $stmt->fetchColumn($i);
        if (!is_null($dtObj)) {
            echo "Expected NULL for column " . ($i + 1) . " but got: ";
            var_dump($dtObj);
        } 
    } while (++$i < count($columns)); 
}

try {
    $conn = connect();
    
    // create a test table 
    $tableName = "TestNullDateTime";
    $columns = array('c1', 'c2', 'c3', 'c4', 'c5', 'c6');
    $colMeta = array(new ColumnMeta('bigint', $columns[0]),
                     new ColumnMeta('bigint', $columns[1]));
                     new ColumnMeta('decimal(5,2)', $columns[2]),
                     new ColumnMeta('numeric(5,2)', $columns[3]),
                     new ColumnMeta('decimal(38,4)', $columns[4]),
                     new ColumnMeta('numeric(38,4)', $columns[5]),
    createTable($conn, $tableName, $colMeta);

    $value = null;
    $query = "INSERT INTO $tableName VALUES(?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    for ($i = 0; $i < count($columns); $i++) {
        $stmt->bindParam($i+1, $value, PDO::PARAM_NULL);
    }
    $stmt->execute();
    
    $query = "SELECT * FROM $tableName";
    
    runTest($conn, $query, $columns);
    runTest($conn, $query, $columns, true);
    
    dropTable($conn, $tableName);
    
    echo "Done\n";
    
    unset($stmt); 
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Done
