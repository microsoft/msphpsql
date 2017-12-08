--TEST--
Transaction operations: commit successful transactions
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

function printContent($conn)
{
    global $tableName;
    $query = "SELECT * FROM $tableName";
    
    // To ensure we always get the first row, use a where clause
    $stmt = AE\executeQuery($conn, $query, "GroupId = ?", array("ID1"));
    
    // Fetch first row
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    print_r($row);
}

function runQuery($conn, $sql, $params)
{
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt) {
            sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $sql, $params);
    }
    if (!$stmt) {
        fatalError("Failed to run query $sql");
    }
    return $stmt;
}

// connect
$conn = AE\connect();

$tableName = 'srv_036_test';
// Create table
// Do not encrypt the integer columns because of the operations required later
$columns = array(new AE\ColumnMeta('VARCHAR(10)', 'GroupId', 'primary key'),
                 new AE\ColumnMeta('INT', 'Accepted', null, null, true),
                 new AE\ColumnMeta('INT', 'Tentative', 'NOT NULL CHECK (Tentative >= 0)', null, true));
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}
sqlsrv_free_stmt($stmt);

// Set initial data
if (AE\isColEncrypted()) {
    $stmt = sqlsrv_query($conn, 
                         "INSERT INTO $tableName VALUES (?,?,?),(?,?,?)", 
                         array(array('ID1', null, null, SQLSRV_SQLTYPE_VARCHAR(10)),
                               array(12, null, null, SQLSRV_SQLTYPE_INT),
                               array(5, null, null, SQLSRV_SQLTYPE_INT), 
                               array('ID102', null, null, SQLSRV_SQLTYPE_VARCHAR(10)),
                               array(20, null, null, SQLSRV_SQLTYPE_INT),
                               array(1, null, null, SQLSRV_SQLTYPE_INT)));
} else {
    $sql = "INSERT INTO $tableName VALUES ('ID1','12','5'),('ID102','20','1')";
    $stmt = sqlsrv_query($conn, $sql);
}
if (!$stmt) {
    fatalError("Failed to insert data\n");
}

//Initiate transaction
sqlsrv_begin_transaction($conn) ?: die(print_r(sqlsrv_errors(), true));

// Update parameters
$count = 4;
$groupId = "ID1";
$params = array($count, $groupId);

// Update Accepted column
$sql = "UPDATE $tableName SET Accepted = (Accepted + ?) WHERE GroupId = ?";
$stmt1 = runQuery($conn, $sql, $params);

// Update Tentative column
$sql = "UPDATE $tableName SET Tentative = (Tentative - ?) WHERE GroupId = ?";
$stmt2 = runQuery($conn, $sql, $params);

// Commit the transactions
if ($stmt1 && $stmt2) {
    sqlsrv_commit($conn);
} else {
    echo "\nERROR: $stmt1 and $stmt2 should be valid\n";
    sqlsrv_rollback($conn);
    echo "\nTransactions were rolled back.\n";
}

printContent($conn);

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
Array
(
    [GroupId] => ID1
    [Accepted] => 16
    [Tentative] => 1
)
Done
