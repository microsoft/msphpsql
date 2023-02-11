--TEST--
Send a large amount (10MB) using encryption. In a Linux CI environment use a smaller size.
--SKIPIF--
<?php require('skipif_azure_dw.inc'); ?>
--FILE--
<?php

#[AllowDynamicProperties]
class my_stream
{
    public $total_read = 0;
     
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->total_read = 0;
        return true;
    }

    public function stream_read($count)
    {
        global $limit;
        if ($this->total_read > $limit) {
            return 0;
        }
        global $packets;
        ++$packets;
        
        // 8192 is passed to stream_read as $count
        $str = str_repeat("A", $count);
        $this->total_read += $count;
        return $str;
    }

    public function stream_write($data)
    {
    }

    public function stream_tell()
    {
        return $this->total_read;
    }

    public function stream_eof()
    {
        global $limit;
        return $this->total_read > $limit;
    }

    public function stream_seek($offset, $whence)
    {
        // For the purpose of this test only support SEEK_SET to $offset 0
        if ($whence == SEEK_SET && $offset == 0) {
            $this->total_read = $offset;
            return true;
        }
        return false;
    }
}

function isServerInLinux($conn)
{
    // This checks if SQL Server is running in Linux (Docker) in a CI environment
    // If so, the major version must be 14 or above (SQL Server 2017 or above)
    $serverVer = sqlsrv_server_info($conn)['SQLServerVersion'];
    if (explode('.', $serverVer)[0] < 14) {
        return false;
    }

    // The view sys.dm_os_host_info, available starting in SQL Server 2017, is somewhat similar to sys.dm_os_windows_info.
    // It returns one row that displays operating system version information and has columns to differentiate
    // Windows and Linux.
    $stmt = sqlsrv_query($conn, 'SELECT host_platform FROM sys.dm_os_host_info');
    if ($stmt && sqlsrv_fetch($stmt)) {
        $host = sqlsrv_get_field($stmt, 0);
        return ($host === 'Linux');
    }
    
    return false;
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_ALL);

$packets = 0;
$limit = 20000000;

$result = stream_wrapper_register("mystr", "my_stream");
if (!$result) {
    die("Couldn't register stream class.");
}

require_once('MsCommon.inc');

$conn = Connect(array( 'Encrypt' => true, 'TrustServerCertificate' => true ));
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// In a Linux CI environment use a smaller size
if (isServerInLinux($conn)) {
    $limit /= 100;
}

$stmt = sqlsrv_query($conn, "IF OBJECT_ID('test_lob', 'U') IS NOT NULL DROP TABLE test_lob");
if ($stmt !== false) {
    sqlsrv_free_stmt($stmt);
}

$stmt = sqlsrv_query($conn, "CREATE TABLE test_lob (id tinyint, stuff varbinary(max))");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

$lob = fopen("mystr://test_data", "rb");
if (!$lob) {
    die("failed opening test stream.\n");
}
$stmt = sqlsrv_query($conn, "INSERT INTO test_lob (id, stuff) VALUES (?,?)", array( 1, array( $lob, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

while ($result = sqlsrv_send_stream_data($stmt)) {
    ++$packets;
}
if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Number of packets sent should be $limit / 8192 (rounded up)
// Length of the varbinary = $packetsSent * 8192 + 1 (HEX 30 appended at the end)
$packetsSent = ceil($limit / 8192);
$length = $packetsSent * 8192 + 1;
if ($packets != $packetsSent) {
    echo "$packets sent.\n";
}

$stmt = sqlsrv_query($conn, "SELECT LEN(stuff) FROM test_lob");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
while ($result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
    if ($result[0] != $length) {
        print_r($result);
    }
}

sqlsrv_query($conn, "DROP TABLE test_lob");

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

sleep(10);    // since this is a long test, we give the database some time to finish

echo "Done\n";
?>
--EXPECT--
Done
