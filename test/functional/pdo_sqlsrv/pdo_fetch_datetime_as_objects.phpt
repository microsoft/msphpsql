--TEST--
Test attribute PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE for date and datetime columns 
--DESCRIPTION--
Test attribute PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE for date, datetime2 and
smalldatetime columns. The input values are based on current date and timestamp 
and they are retrieved either as strings or date time objects. Note that the
existing attributes ATTR_STRINGIFY_FETCHES and SQLSRV_ATTR_FETCHES_NUMERIC_TYPE
should have no effect on data retrieval.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkStringValues($obj, $columns, $values)
{
    $size = count($values);
    $objArray = (array)$obj;    // turn the object into an associated array
    
    for ($i = 0; $i < $size; $i++) {
        $col = $columns[$i];
        $val = $objArray[$col];

        $failed = false;
        switch ($i) {
        case 1:     // datetime2 data value might have padded zeroes
            if (strpos($val, $values[$i]) === false) {
                $failed = true;
            }
            break;
        default:    // first and last column values should match exactly
            if ($val != $values[$i]) {
                $failed = true;
            }
            break;
        }
        
        if ($failed) {
            echo "Expected $values[$i] for column $col but got: ";
            var_dump($val);
        } 
    }
}

function checkDTObjectValues($row, $columns, $values, $fetchStyle) 
{
    $size = count($values);
    
    for ($i = 0; $i < $size; $i++) {
        $col = $columns[$i];
        if ($fetchStyle == PDO::FETCH_NUM) {
            $dtObj = $row[$i];
        } else {
            // assume PDO::FETCH_ASSOC
            $dtObj = $row[$col];
        }
        checkColumnDTValue($i, $col, $values, $dtObj);
    }
}

function checkColumnDTValue($index, $column, $values, $dtObj)
{
    // expected datetime value as a string
    $dtime = date_create($values[$index]);
    $dtExpected = $dtime->format('Y-m-d H:i:s.u');

    // actual datetime value from date time object to string
    $dtActual = date_format($dtObj, 'Y-m-d H:i:s.u');
    if ($dtActual != $dtExpected) {
        echo "Expected $dtExpected for column $column but got $dtActual\n";
    } 
}

function runTest($conn, $query, $columns, $values, $useBuffer = false)
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
    $obj = $stmt->fetch(PDO::FETCH_OBJ);
    checkStringValues($obj, $columns, $values);

    // fetch_numeric off, fetch_datetime on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkDTObjectValues($row, $columns, $values, PDO::FETCH_NUM);

    // fetch_numeric on, fetch_datetime on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkDTObjectValues($row, $columns, $values, PDO::FETCH_ASSOC);
    
    // ATTR_STRINGIFY_FETCHES should have no effect when fetching date time objects 
    // Setting it to true only converts numeric values to strings when fetching
    // See http://www.php.net/manual/en/pdo.setattribute.php for details
    // stringify on, fetch_numeric off, fetch_datetime on
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $i = 0;
    do {
        $stmt->execute();
        $dtObj = $stmt->fetchColumn($i);
        checkColumnDTValue($i, $columns[$i], $values, $dtObj);
    } while (++$i < count($columns));
    
    // reset stringify to off
    // fetch_numeric off, fetch_datetime off
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    checkStringValues($obj, $columns, $values);
    
    // conn attribute fetch_datetime on, but statement attribute fetch_datetime off --
    // expected strings to be returned because statement attribute overrides the 
    // connection attribute
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt = $conn->prepare($query, $options);
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt->execute();
    $obj = $stmt->fetch(PDO::FETCH_OBJ);
    checkStringValues($obj, $columns, $values);
    
    // conn attribute fetch_datetime unchanged, but statement attribute fetch_datetime on --
    // expected datetime objects to be returned (this time no need to prepare the statement)
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_NUM);
    checkDTObjectValues($row, $columns, $values, PDO::FETCH_NUM);

    // likewise, conn attribute fetch_datetime off, but statement attribute 
    // fetch_datetime on -- expected datetime objects to be returned
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt = $conn->prepare($query, $options);
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkDTObjectValues($row, $columns, $values, PDO::FETCH_ASSOC);
    
    // conn attribute fetch_datetime unchanged, but statement attribute fetch_datetime off --
    // expected strings to be returned (again no need to prepare the statement)
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, false);
    $stmt->execute();
    $obj = $stmt->fetch(PDO::FETCH_OBJ);
    checkStringValues($obj, $columns, $values);
    
    // last test: set statement attribute fetch_datetime on with no change to 
    // prepared statement -- expected datetime objects to be returned
    $stmt->setAttribute(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE, true);
    $stmt->execute();
    $i = 0;
    do {
        $stmt->execute();
        $dtObj = $stmt->fetchColumn($i);
        checkColumnDTValue($i, $columns[$i], $values, $dtObj);
    } while (++$i < count($columns));
}

try {
    date_default_timezone_set('America/Los_Angeles');

    $conn = connect();
    
    // generate input date time values
    $values = array();
    
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    
    $dt2Value = $now . '.' . rand(999, 9999999);
    $sdtValue = $today . ' 00:00:00';
    
    array_push($values, $today);
    array_push($values, $dt2Value);
    array_push($values, $sdtValue);
    
    // create a test table with the above input date time values
    $tableName = "TestDateTime";
    $columns = array('c1', 'c2', 'c3');
    $colMeta = array(new ColumnMeta('date', $columns[0]),
                     new ColumnMeta('datetime2', $columns[1]),
                     new ColumnMeta('smalldatetime', $columns[2]));
    createTable($conn, $tableName, $colMeta);

    $query = "INSERT INTO $tableName VALUES(?, ?, ?)";
    $stmt = $conn->prepare($query);
    for ($i = 0; $i < count($columns); $i++) {
        $stmt->bindParam($i+1, $values[$i], PDO::PARAM_LOB);
    }
    $stmt->execute();

    $query = "SELECT * FROM $tableName";
    
    runTest($conn, $query, $columns, $values);
    runTest($conn, $query, $columns, $values, true);
    
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