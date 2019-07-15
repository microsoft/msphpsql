--TEST--
Test attribute PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE for datetime types with null values
--DESCRIPTION--
Test attribute PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE for different datetime types with
null values. Whether retrieved as strings or date time objects should return NULLs.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkNullStrings($row, $columns)
{
    $size = count($columns);
    for ($i = 0; $i < $size; $i++) {
        $col = $columns[$i];
        $val = $row[$i];
        if (!is_null($val)) {
            echo "Expected NULL for column $col but got: ";
            var_dump($val);
        } 
    }
}

function checkNullDTObjects($row, $columns, $fetchStyle) 
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
    
    // fetch_numeric off, fetch_datetime off 
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkNullStrings($row, $columns);

    // fetch_numeric off, fetch_datetime on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkNullDTObjects($row, $columns, PDO::FETCH_ASSOC);

    // fetch_numeric on, fetch_datetime on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_BOTH);
    checkNullDTObjects($row, $columns, PDO::FETCH_BOTH);
    
    // conn attribute fetch_datetime on, but statement attribute fetch_datetime off --
    // expected strings to be returned because statement attribute overrides the 
    // connection attribute
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkNullStrings($row, $columns);
    
    // conn attribute fetch_datetime unchanged, but statement attribute fetch_datetime on --
    // expected datetime objects to be returned (this time no need to prepare the statement)
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkNullDTObjects($row, $columns, PDO::FETCH_ASSOC);

    // likewise, conn attribute fetch_datetime off, but statement attribute 
    // fetch_datetime on -- expected datetime objects to be returned
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt = $conn->prepare($query, $options);
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_BOTH);
    checkNullDTObjects($row, $columns, PDO::FETCH_BOTH);
    
    // conn attribute fetch_datetime unchanged, but statement attribute fetch_datetime off --
    // expected strings to be returned (again no need to prepare the statement)
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkNullStrings($row, $columns);
    
    // last test: set statement attribute fetch_datetime on with no change to 
    // prepared statement -- expected datetime objects to be returned
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
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
    $colMeta = array(new ColumnMeta('date', $columns[0]),
                     new ColumnMeta('datetime', $columns[1]),
                     new ColumnMeta('smalldatetime', $columns[2]),
                     new ColumnMeta('datetime2', $columns[3]),
                     new ColumnMeta('datetimeoffset', $columns[4]),
                     new ColumnMeta('time', $columns[5]));
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
