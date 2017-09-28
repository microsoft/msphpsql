--TEST--
Test emulate prepare with mix bound param encodings and positional placeholders (i.e., using '?' as placeholders)
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );
 
$cnn = connect();

$pdo_options = array();
if ( !is_col_encrypted() )
{
    // Emulate prepare and direct query are not supported with Always Encrypted
    $pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
    $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
}
$pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
$pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

$tbname = "watchdog";
create_table( $cnn, $tbname, array( new columnMeta( "nvarchar(128)", "system_encoding" ), new columnMeta( "nvarchar(128)", "utf8_encoding" ), new columnMeta( "varbinary(max)", "binary_encoding" )));

$system_param = 'system encoded string';
$utf8_param = '가각ácasa';
$binary_param = fopen('php://memory', 'a');
fwrite($binary_param, 'asdgasdgasdgsadg');
rewind($binary_param);

$inputs = array( "system_encoding" => $system_param,
                 "utf8_encoding" => new bindParamOp( 2, $utf8_param, "PDO::PARAM_STR", 0, "PDO::SQLSRV_ENCODING_UTF8" ),
                 "binary_encoding" => new bindParamOp( 3, $binary_param, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY" ));

insert_row( $cnn, $tbname, $inputs, "prepareBindParam" );

$data = select_all( $cnn, $tbname );
var_dump( $data );

DropTable( $cnn, $tbname );
unset( $st );
unset( $cnn );

?>
--EXPECT--
array(1) {
  [0]=>
  array(6) {
    ["system_encoding"]=>
    string(21) "system encoded string"
    [0]=>
    string(21) "system encoded string"
    ["utf8_encoding"]=>
    string(12) "가각ácasa"
    [1]=>
    string(12) "가각ácasa"
    ["binary_encoding"]=>
    string(16) "asdgasdgasdgsadg"
    [2]=>
    string(16) "asdgasdgasdgsadg"
  }
}