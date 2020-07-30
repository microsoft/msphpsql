--TEST--
Test sqlsrv_get_field() with empty and null varchar fields using incorrect PHP types 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once("MsCommon.inc");

$tableName = 'sqlsrv_get_field_nulls';

// connect
$conn = connect();
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

// Create table
dropTable($conn, $tableName);

$query = "CREATE TABLE $tableName (ID VARCHAR(10) NULL)";
$stmt = sqlsrv_query($conn, $query);

// Insert empty string and NULL value
$query = "INSERT INTO $tableName VALUES (''), (NULL)";
$stmt = sqlsrv_query($conn, $query);
if (!$stmt) {
    fatalError("Failed to insert rows");
}

// Fetch data
$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_prepare($conn, $query);
if (!$stmt) {
    fatalError("Failed to prepare query");
}

sqlsrv_execute($stmt);

while (sqlsrv_fetch($stmt)) {
    $field = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_FLOAT);
    var_dump($field);
}

sqlsrv_execute($stmt);

while (sqlsrv_fetch($stmt)) {
    $field = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_INT);
    var_dump($field);
}

sqlsrv_execute($stmt);

// When fetched as datetime, an empty string means indicates the current time
while (sqlsrv_fetch($stmt)) {
    $field = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_DATETIME);
    
    if (is_null($field)) {
        var_dump($field);
    } else {
        $date = date_create('');
        $str1 = date_format($date, "Y/m/d");
        $str2 = date_format($field, "Y/m/d");
        
        // It suffices to compare just the date
        if (strcmp($str1, $str2)) {
            echo "Fetched value is unexpected:\n";
            var_dump($field);
        }
    }
}

dropTable($conn, $tableName);

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
float(0)
NULL
int(0)
NULL
NULL
Done
