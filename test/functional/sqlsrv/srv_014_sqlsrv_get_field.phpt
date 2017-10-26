--TEST--
sqlsrv_get_field() using SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY)
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect();
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

$tableName = GetTempTableName();

// Create table
$query = "CREATE TABLE ".$tableName." (ID VARCHAR(10))";
$stmt = sqlsrv_query($conn, $query);

$query = "INSERT INTO ".$tableName." VALUES ('1998.1'),('-2004.2436'),('4.2EUR')";
$stmt = sqlsrv_query($conn, $query) ?: die(print_r(sqlsrv_errors(), true));

// Fetch data
$query = "SELECT * FROM ".$tableName;
$stmt = sqlsrv_query($conn, $query) ?: die(print_r(sqlsrv_errors(), true));

while (sqlsrv_fetch($stmt)) {
    $field = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    var_dump(get_resource_type($field));

    while (!feof($field)) {
        echo fread($field, 100)."\n";
    }
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
string(6) "stream"
1998.1
string(6) "stream"
-2004.2436
string(6) "stream"
4.2EUR
Done
