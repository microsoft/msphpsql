--TEST--
scrollable result sets.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', false);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

require_once('MsCommon.inc');

function hasRows($stmt, $expectedFail)
{
    $rows = sqlsrv_has_rows($stmt);
    if ($expectedFail) {
        if ($rows == true) {
            die("Shouldn't have rows");
        }
    } else {
        if ($rows != true) {
            die("Should have rows");
        }
    }
}

function countRows($stmt, $numRows, $cursorType, $initialCount = 0) 
{
    $row_count = $initialCount;
    while ($row = sqlsrv_fetch($stmt)) {
       ++$row_count;
    }
    if($row === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    if ($row_count != $numRows) {
        echo "ERROR: $row_count rows retrieved on the $cursorType cursor\n";
    }
}

function insertOneRow($conn, $tableName, $idx, $expectedFail)
{
    $res = null;
    $stmt = AE\insertRow($conn, $tableName, array('id' => $idx, 'value' => 'Row ' . $idx), $res, AE\INSERT_QUERY_PARAMS);

    if (!$stmt || $res === false) {
        fatalError("failed to insert row $idx!\n");
    }
    hasRows($stmt, $expectedFail);
    sqlsrv_free_stmt($stmt);
}

$conn = AE\connect();
$tableName = 'ScrollTest';
$numRows = 4;

$columns = array(new AE\ColumnMeta('int', 'id'),
                 new AE\ColumnMeta('char(10)', 'value'));
$stmt = AE\createTable($conn, $tableName, $columns);

$rows = sqlsrv_has_rows($stmt);
if($rows == true) {
    die("Shouldn't have rows");
}
sqlsrv_free_stmt($stmt);

for ($i = 1; $i <= $numRows; $i++) {
    insertOneRow($conn, $tableName, $i, true);
}

$query = "SELECT * FROM $tableName";
$options = array('Scrollable' => SQLSRV_CURSOR_FORWARD);
$stmt = sqlsrv_query($conn, $query, array(), $options);

hasRows($stmt, false);
countRows($stmt, $numRows, 'forward only'); 
sqlsrv_free_stmt($stmt);

$options = array('Scrollable' => 'static'); 
$stmt = sqlsrv_query($conn, $query, array(), $options);

$result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, 4);
if($result !== null) {
    die("Should have failed with an invalid row number");
}
hasRows($stmt, false);
// this is empty
print_r(sqlsrv_errors());
$result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, -1);
if($result !== null) {
    die("Should have failed with an invalid row number");
}
// this is empty
print_r(sqlsrv_errors());

// expected an error here
$rows = sqlsrv_rows_affected($stmt);
$message = !empty(sqlsrv_errors()) ? sqlsrv_errors()[0]['message'] : '';
$expected = 'This function only works with statements that are not scrollable.';
if (strcmp($message, $expected)) {
    echo "Expected this error message: \'$expected\'\nbut it is: \'$message\'\n";
}    

$rows = sqlsrv_num_rows($stmt);
if ($rows != $numRows) {
    echo "Error: Query returned $rows rows\n";
}

$row = 3; 
$result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, $row);
do {
    $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, $row);
    if($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $field1 = sqlsrv_get_field($stmt, 0);
    $field2 = sqlsrv_get_field($stmt, 1);
    $idx = $row + 1;
    
    if ($field1 != $idx || trim($field2) != "Row $idx")
        echo "Field values unexpected $field1 $field2!\n";
        
    $row = $row - 1;
} while($row >= 0);
sqlsrv_free_stmt($stmt);

$options = array('Scrollable' => 'static');
$stmt = sqlsrv_query($conn, $query, array(), $options);

hasRows($stmt, false);
countRows($stmt, $numRows, 'static'); 
sqlsrv_free_stmt($stmt);

$options = array('Scrollable' => 'dynamic');
$stmt = sqlsrv_query($conn, $query, array(), $options);

sqlsrv_fetch($stmt);
sqlsrv_fetch($stmt);

insertOneRow($conn, $tableName, 5, true);
insertOneRow($conn, $tableName, 6, true);
$numRows = 6;

// to account for the two fetches above
countRows($stmt, $numRows, 'dynamic', 2);
sqlsrv_free_stmt($stmt);

$options = array('Scrollable' => SQLSRV_CURSOR_STATIC);
$stmt = sqlsrv_query($conn, $query, array(), $options);

$row_count = sqlsrv_num_rows($stmt);
if($row_count != $numRows) {
    die("sqlsrv_num_rows should have returned 6 rows in the static cursor\n");
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, -1);
if($row !== null) {
    die("sqlsrv_fetch_array should have returned null\n");
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, 6);
if($row !== null) {
    die("sqlsrv_fetch_array should have returned null\n");
}

$options = array('Scrollable' => SQLSRV_CURSOR_DYNAMIC);
$stmt = sqlsrv_query($conn, $query, array(), $options);

$result = sqlsrv_num_rows($stmt);
if($result !== false) {
    die("sqlsrv_num_rows should have failed for a dynamic cursor.");
}
sqlsrv_fetch($stmt);
sqlsrv_fetch($stmt);

$stmt2 = AE\executeQuery($conn, "DELETE FROM $tableName", "id = ?", array(2));
if($stmt2 === false) {
    die(print_r(sqlsrv_errors(), true));
}

$row = sqlsrv_get_field($stmt, 0);
if($row !== false) {
    die("sqlsrv_get_field should have returned false retrieving a field deleted by another query");
}
$error = sqlsrv_errors()[0];  
$message = $error['message'];
$sqlstate = $error['SQLSTATE'];
if (strcmp($sqlstate, 'HY109') || strpos($message, 'Invalid cursor position') === false) {
    die("Unexpected SQL state $sqlstate or error \'$message\'");
}

// verify the sqlsrv_fetch_object is working
$obj = sqlsrv_fetch_object($stmt, null, array(null), SQLSRV_SCROLL_LAST, 1);
if($obj === false) {
    print_r(sqlsrv_errors());
} else {
    if ($obj->id != $numRows || trim($obj->value) != "Row $numRows")
        echo "Field values unexpected $obj->id $obj->value!\n";
}
sqlsrv_free_stmt($stmt);
    
dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Test succeeded.\n";

?>
--EXPECT--
Test succeeded.
