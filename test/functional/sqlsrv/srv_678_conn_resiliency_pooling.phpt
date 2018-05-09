--TEST--
GitHub issue #678 - Idle Connection Resiliency doesn't work with Connection Pooling
--DESCRIPTION--
Verifies that the issue has been fixed with ODBC 17.1
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_protocol_not_tcp.inc');
      require('skipif_version_less_than_2k14.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

function checkODBCVersion($conn)
{
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    $vers = explode(".", $msodbcsql_ver);

    if ($vers[0] >= 17 && $vers[1] > 0){
        return true;
    } else {
        return false;
    }
}

function breakConnection($conn, $conn_break)
{
    $stmt1 = sqlsrv_query($conn, "SELECT @@SPID");
    if (sqlsrv_fetch($stmt1)) {
        $spid=sqlsrv_get_field($stmt1, 0);
    }

    $stmt2 = sqlsrv_prepare($conn_break, "KILL ".$spid);
    sqlsrv_execute($stmt2);
    sleep(1);
}

// create a connection for create the table and breaking other connections
$conn_break = sqlsrv_connect($server, array("Database"=>$database, "UID"=>$uid, "PWD"=>$pwd));

if (! checkODBCVersion($conn_break)) {
    echo "Done\n";
    return;
}

$tableName = "test_connres";

dropTable($conn_break, $tableName);

$sql = "CREATE TABLE $tableName (c1 INT, c2 VARCHAR(40))";
$stmt = sqlsrv_query($conn_break, $sql);

$sql = "INSERT INTO $tableName VALUES (?, ?)";
    for ($t = 200; $t < 209; $t++) {
        $ts = substr(sha1($t), 0, 5);
        $params = array($t, $ts);
        $stmt = sqlsrv_prepare($conn_break, $sql, $params);
        sqlsrv_execute($stmt);
    }

// first connection
$connectionInfo = array("Database"=>$database, "UID"=>$uid, "PWD"=>$pwd, 
                        "ConnectionPooling"=>true, "ConnectRetryCount"=>10,
                        "ConnectRetryInterval"=>10 );
                         
$conn = sqlsrv_connect($server, $connectionInfo);

breakConnection($conn, $conn_break);

$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $query, array(), array("Scrollable"=>"buffered"));
if ($stmt === false) {
    echo "Error in connection 1.\n";
    print_r(sqlsrv_errors());
} else {
    $row_count = sqlsrv_num_rows($stmt);
    if ($row_count != 9) {
        echo "Unexpected $row_count rows in result set.\n";
    }
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// second connection
$conn = sqlsrv_connect($server, $connectionInfo);

breakConnection($conn, $conn_break);

// would connection be able to resume here if connection pooling is enabled?
$stmt2 = sqlsrv_query($conn, $query);
if ($stmt2 === false) {
    echo "Error in connection 2.\n";
    print_r(sqlsrv_errors());
} else {
    $num_fields = sqlsrv_num_fields($stmt2);
    if ($num_fields != 2) {
        echo "Unexpected $num_fields columns in result set.\n";
    }
}

dropTable($conn, $tableName);

echo "Done\n";

sqlsrv_free_stmt($stmt2);
sqlsrv_close($conn);
sqlsrv_close($conn_break);

?>
--EXPECT--
Done