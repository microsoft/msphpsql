--TEST--
send a large amount (10MB) using encryption.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
class my_stream {

    var $total_read = 0;
     
    function stream_open ($path, $mode, $options, &$opened_path )
    {
        $this->total_read = 0;
        return true;
    }

    function stream_read( $count )
    {
        if( $this->total_read > 20000000 ) {
            return 0;
        }
        global $packets;
        ++$packets;
        $str = str_repeat( "A", $count );
        $this->total_read += $count;
        return $str;
    }

    function stream_write($data)
    {
    }

    function stream_tell()
    {
        return $this->total_read;
    }

    function stream_eof()
    {
        return $this->total_read > 20000000;
    }

    function stream_seek($offset, $whence)
    {
    }
}

set_time_limit(0);
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_ALL );

$packets = 0;

$result = stream_wrapper_register( "mystr", "my_stream" );
if( !$result ) {
    die( "Couldn't register stream class." );
}

require( 'MsCommon.inc' );

$conn = Connect(array( 'Encrypt' => true, 'TrustServerCertificate' => true ));
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('test_lob', 'U') IS NOT NULL DROP TABLE test_lob" );
if( $stmt !== false ) sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "CREATE TABLE test_lob (id tinyint, stuff varbinary(max))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt( $stmt );

$lob = fopen( "mystr://test_data", "rb" );
if( !$lob ) {
    die( "failed opening test stream.\n" );
}
$stmt = sqlsrv_query( $conn, "INSERT INTO test_lob (id, stuff) VALUES (?,?)", array( 1, array( $lob, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

while( $result = sqlsrv_send_stream_data( $stmt )) {
    ++$packets;
}
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
echo "$packets sent.\n";

$stmt = sqlsrv_query( $conn, "SELECT LEN(stuff) FROM test_lob" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
while( $result = sqlsrv_fetch_array( $stmt )) {
    print_r( $result );
}

sqlsrv_query( $conn, "DROP TABLE test_lob" );

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );  

sleep(10);    // since this is a long test, we give the database some time to finish
?>
--EXPECT--
2442 sent.
Array
(
    [0] => 20004865
    [] => 20004865
)
